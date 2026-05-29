<?php
/**
 * Venue config template manager class.
 *
 * PHP version 5
 *
 * @category VenueConfigTemplateManager
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * Venue config template manager class.
 *
 * @category VenueConfigTemplateManager
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class VenueConfigTemplateManager extends FOGManagerController
{
    /**
     * The base table name.
     *
     * @var string
     */
    public $tablename = 'venueConfigTemplate';
    /**
     * Install the venueConfigTemplate table.
     *
     * @return bool
     */
    public function install()
    {
        $sql = Schema::createTable(
            $this->tablename,
            true,
            array(
                'vctID',
                'vctKey',
                'vctLabel',
                'vctType',
                'vctDefault',
                'vctPlaceholder',
                'vctOptions',
                'vctRequired',
                'vctDescription',
                'vctValidation',
                'vctValidationMsg',
                'vctApiMapping',
                'vctGroup',
                'vctOrder',
            ),
            array(
                'INTEGER',
                'VARCHAR(50)',
                'VARCHAR(100)',
                'VARCHAR(20)',
                'TEXT',
                'VARCHAR(255)',
                'TEXT',
                'INTEGER',
                'VARCHAR(255)',
                'VARCHAR(255)',
                'VARCHAR(255)',
                'VARCHAR(100)',
                'VARCHAR(100)',
                'INTEGER',
            ),
            array(
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
            ),
            array(
                false,
                false,
                false,
                "'text'",
                false,
                false,
                false,
                '0',
                false,
                false,
                false,
                false,
                false,
                '0',
            ),
            array(
                'vctID',
                'vctKey',
            ),
            'InnoDB',
            'utf8',
            'vctID',
            'vctID'
        );
        self::$DB->query($sql);
        // Seed default config templates if table is empty
        $count = self::$DB->query(
            "SELECT COUNT(*) AS cnt FROM `{$this->tablename}`"
        )->fetch()->get('cnt');
        if ((int)$count === 0) {
            $this->_seedDefaults();
        }
        return true;
    }
    /**
     * Seed the default config templates (initial install only).
     *
     * @return void
     */
    private function _seedDefaults()
    {
        $result = $this->reseedDefaults(false);
        unset($result);
    }

    /**
     * Idempotent reseed of the canonical default templates.
     *
     * Iterates the canonical list and:
     *  - INSERTs any template key that is missing from the table
     *  - UPDATEs existing rows only when $overwrite is true (and only the
     *    fields that are part of the seed definition — never the row's id)
     *  - Leaves existing rows untouched when $overwrite is false
     *
     * @param bool $overwrite When true, rewrite existing rows to match the
     *                        canonical defaults. When false (default), only
     *                        insert missing keys.
     *
     * @return array{inserted:int,updated:int,skipped:int}
     */
    public function reseedDefaults($overwrite = false)
    {
        $stats = array('inserted' => 0, 'updated' => 0, 'skipped' => 0);
        $existing = array();
        $rows = self::$DB->query(
            "SELECT `vctID`, `vctKey` FROM `{$this->tablename}`"
        )->fetch(PDO::FETCH_ASSOC, 'fetch_all')->get();
        foreach ((array)$rows as $row) {
            if (isset($row['vctKey'])) {
                $existing[$row['vctKey']] = (int)$row['vctID'];
            }
        }
        $cols = '`vctKey`,`vctLabel`,`vctType`,`vctDefault`,`vctPlaceholder`,`vctOptions`,`vctRequired`,`vctDescription`,`vctApiMapping`,`vctGroup`,`vctOrder`';
        foreach (self::canonicalTemplates() as $t) {
            $key = $t[0];
            if (isset($existing[$key])) {
                if (!$overwrite) {
                    $stats['skipped']++;
                    continue;
                }
                // Update the existing row to match the canonical values
                $set = sprintf(
                    "`vctLabel`='%s',`vctType`='%s',`vctDefault`='%s',`vctPlaceholder`='%s',"
                    . "`vctOptions`='%s',`vctRequired`='%s',`vctDescription`='%s',"
                    . "`vctApiMapping`='%s',`vctGroup`='%s',`vctOrder`='%s'",
                    self::$DB->sanitize($t[1]),
                    self::$DB->sanitize($t[2]),
                    self::$DB->sanitize($t[3]),
                    self::$DB->sanitize($t[4]),
                    self::$DB->sanitize($t[5]),
                    self::$DB->sanitize($t[6]),
                    self::$DB->sanitize($t[7]),
                    self::$DB->sanitize($t[8]),
                    self::$DB->sanitize($t[9]),
                    self::$DB->sanitize($t[10])
                );
                self::$DB->query(
                    "UPDATE `{$this->tablename}` SET {$set} WHERE `vctID`=" . $existing[$key]
                );
                $stats['updated']++;
                continue;
            }
            $vals = array_map(function ($v) {
                return "'" . self::$DB->sanitize($v) . "'";
            }, $t);
            self::$DB->query(
                "INSERT INTO `{$this->tablename}` ({$cols}) VALUES (" . implode(',', $vals) . ")"
            );
            $stats['inserted']++;
        }
        return $stats;
    }

    /**
     * Canonical list of default templates.
     *
     * Each array: key, label, type, default, placeholder, options, required,
     * description, apiMapping, group, order.
     *
     * @return array[]
     */
    public static function canonicalTemplates()
    {
        return array(
            // Messaging
            array('MQTT_BROKER_URI','MQTT Broker URI','text','','mqtt://10.x.x.x:1883','',1,'MQTT broker URI for the venue server','','Messaging',0),
            array('NATS_HOST','NATS Host','text','','10.x.x.x','',1,'NATS server hostname or IP','','Messaging',1),
            array('NATS_PORT','NATS Port','number','4222','','',1,'NATS server port','','Messaging',2),
            array('NATS_USERNAME','NATS Username','text','cluster','','',1,'NATS authentication username','','Messaging',3),
            array('NATS_PASSWORD','NATS Password','password','','','',1,'NATS authentication password','','Messaging',4),
            array('NATS_CLOUD_REGION','NATS Cloud Region','select','us','','us|eu|eu-dev',1,'Cloud region for NATS cluster routing','','Messaging',5),
            // Venue
            array('VENUE_ID','Venue ID','text','','','',1,'MongoDB ObjectId identifying this venue','id','Venue',6),
            array('DEV','Dev Mode','checkbox','','','',0,'Enable development mode','','Venue',7),
            array('OVERRIDE_HOSTNAME','Override Hostname','text','','','',0,'Override machine hostname reported to platform','','Venue',8),
            array('DEBUGGER','Debugger','checkbox','true','','',0,'Enable debug logging output','','Venue',9),
            array('NEEDS_UPDATE','Needs Update','checkbox','true','','',0,'Flag to trigger update on next boot','','Venue',10),
            // Game
            array('GAME_PATH','Game Path','text','C:\\Users\\f1arcade\\server_daemon\\rfactor\\black-starr-2024-06-20','','',1,'Path to rFactor installation directory','','Game',11),
            array('RFMOD_NAME','RF Mod Name','select','F1A_101325_V1','','F1A_101325_V1|F1A_Prod_62025',1,'rFactor mod package name to load','','Game',12),
            array('RF2_EXIT_TIMEOUT','RF2 Exit Timeout','number','1800','','',1,'Seconds before force-killing rFactor','','Game',13),
            array('EXIT_BETWEEN_RACES','Exit Between Races','checkbox','true','','',0,'Restart rFactor between race sessions','','Game',14),
            array('ESTOP_FILE','E-Stop File Path','text','C:\\f1arcade\\logs\\estop.state','','',1,'Path to emergency stop state file','','Game',15),
            array('UPDATE_WITH_STEAM','Update With Steam','checkbox','','','',0,'Use Steam for game updates','','Game',16),
            array('USE_STEAM','Use Steam','checkbox','','','',0,'Launch game via Steam','','Game',17),
            array('INSTALL_WITH_SHARED_AUTH','Install With Shared Auth','checkbox','','','',0,'Use shared authentication for install','','Game',18),
            array('SKIP_RELOAD_CONFIG_ON_STARTUP','Skip Reload Config On Start','checkbox','true','','',0,'Skip reloading config from cloud on daemon startup','','Game',19),
            // UI
            array('UI_PATH','UI Path','text','C:\\f1arcade\\game-management\\applications\\game-daemon\\src\\services\\webserver\\ui\\','','',1,'Local path to webserver UI files','','UI',20),
            array('DRIVER_TAGS_PATH','Driver Tags Path','text','C:\\f1arcade\\game-management\\applications\\game-daemon\\src\\services\\webserver\\tags\\','','',1,'Local path to driver tag image files','','UI',21),
            array('UI_MEDIA_CONTENT_URL','UI Media Content URL','url','http://localhost:8080','','',1,'Base URL for media content served to the UI','','UI',22),
            array('UI_COMPANION_BASE_URL','Companion Base URL','url','','https://f1arcade.com/us/companion','',1,'Base URL for the mobile companion web app','','UI',23),
            array('UI_RELOAD_AT_RACE_START','Reload UI At Race Start','checkbox','true','','',0,'Force UI reload when a new race session begins','','UI',24),
            array('UI_BUTTON_MAP_VERSION','Button Map Version','number','1','','',1,'Version of the steering wheel button mapping','','UI',25),
            array('UI_LOCALE','UI Locale','select','en-US','','en-US|en-GB|es-ES|ja-JP|fr-FR|de-DE',1,'Display locale for the in-game UI','defaultLocale','UI',26),
            array('UI_VENUE_BRAND','Venue Brand','select','f1a','','f1a|f1b',1,'Brand theme applied to the UI','','UI',27),
            array('UI_MOBILE_ORDERING_ENABLED','Mobile Ordering','checkbox','','','',0,'Enable mobile food/drink ordering integration','','UI',28),
            // Diagnostics
            array('TRACE_PATH','Trace Path','text','C:\\Users\\f1arcade\\server_daemon\\rfactor\\black-starr-2024-06-20\\client\\UserData\\Log','','',0,'Path to rFactor trace log directory','','Diagnostics',29),
            array('TRACE_FILE_PREFIX','Trace File Prefix','text','trace_','','',0,'Filename prefix for trace log files','','Diagnostics',30),
            array('DEBUG_BRAND','Debug Brand','checkbox','','','',0,'Enable brand debugging overlay','','Diagnostics',31),
            array('DUMP_PATH','Crash Dump Path','text','','','',0,'Path where rFactor writes crash minidumps','','Diagnostics',32),
            array('DUMP_FILENAME','Crash Dump Filename','text','rFMini.dmp','','',0,'Expected crash dump filename','','Diagnostics',33),
            array('DUMP_DESTINATION','Dump Destination','text','','','',0,'UNC path for crash dump collection share','','Diagnostics',34),
            array('DUMP_DESTINATION_CREDS','Dump Destination Creds','password','','','',0,'Base64-encoded credentials for crash dump share','','Diagnostics',35),
        );
    }
    /**
     * Uninstall the table.
     *
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall();
    }
}
