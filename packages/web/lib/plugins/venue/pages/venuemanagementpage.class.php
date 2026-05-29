<?php
/**
 * Venue management page.
 *
 * PHP version 5
 *
 * @category VenueManagementPage
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * Venue management page.
 *
 * @category VenueManagementPage
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class VenueManagementPage extends FOGPage
{
    /**
     * Allowed characters in a venue display name (unicode-aware).
     */
    const VENUE_NAME_REGEX = "/^[A-Za-z0-9().,'\\/ -]+$/u";

    /**
     * Allowed characters in a free-form config value.
     */
    const CONFIG_VALUE_REGEX = "/^[A-Za-z0-9_.\\/:\\\\ -]*$/u";

    /**
     * Allowed characters in a config key (uppercase snake).
     */
    const CONFIG_KEY_REGEX = '/^[A-Z0-9_]+$/';

    /**
     * The node this page operates on.
     *
     * @var string
     */
    public $node = 'venue';
    /**
     * Initializes the venue management page.
     *
     * @param string $name Something to lay it out as.
     *
     * @return void
     */
    public function __construct($name = '')
    {
        $this->name = _('Venue Management');
        parent::__construct($this->name);
        $this->menu = array(
            'list' => sprintf(
                self::$foglang['ListAll'],
                _('Venues')
            ),
            'add' => sprintf(
                self::$foglang['CreateNew'],
                _('Venue')
            ),
            'templates' => _('Config Management'),
        );
        global $id;
        if ($id) {
            $this->subMenu = array(
                "$this->linkformat#venue-gen" => _('General'),
                "$this->linkformat#venue-config" => _('Configuration'),
                "$this->linkformat#venue-membership" => _('Membership'),
                "$this->delformat" => self::$foglang['Delete'],
            );
            $this->notes = array(
                _('Venue') => $this->obj->get('name'),
                _('Subnet') => $this->obj->get('subnet'),
            );
        }
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" class='
            . '"toggle-checkboxAction"/>',
            _('Venue Name'),
            _('Subnet'),
            _('Storage Group'),
            _('Host Group'),
        );
        $this->templates = array(
            '<input type="checkbox" name="venue[]" value='
            . '"${id}" class="toggle-action"/>',
            '<a href="?node=venue&sub=edit&id=${id}" title="'
            . _('Edit') . ' ${name}">${name}</a>',
            '${subnet}',
            '${storageGroup}',
            '${hostGroup}',
        );
        $this->attributes = array(
            array(
                'class' => 'parser-false filter-false',
                'width' => 16
            ),
            array(),
            array(),
            array(),
            array(),
        );
    }
    /**
     * List all venues.
     *
     * @return void
     */
    public function index()
    {
        $this->_emitCsrfMeta();
        $this->title = _('All Venues');
        Route::listem('venue');
        $items = json_decode(Route::getData());
        foreach ((array)$items->venues as &$item) {
            $SG = self::getClass('StorageGroup', $item->storagegroupID);
            $HG = self::getClass('Group', $item->hostgroupID);
            $this->data[] = array(
                'id' => $item->id,
                'name' => $item->name,
                'subnet' => $item->subnet,
                'storageGroup' => $SG->isValid()
                    ? '<a href="?node=storage&sub=editStorageGroup&id='
                      . $SG->get('id') . '">'
                      . Initiator::e($SG->get('name')) . '</a>'
                    : _('N/A'),
                'hostGroup' => $HG->isValid()
                    ? '<a href="?node=group&sub=edit&id='
                      . $HG->get('id') . '">'
                      . Initiator::e($HG->get('name')) . '</a>'
                    : _('N/A'),
            );
            unset($item);
        }
        self::$HookManager->processEvent(
            'VENUE_DATA',
            array(
                'headerData' => &$this->headerData,
                'data' => &$this->data,
                'templates' => &$this->templates,
                'attributes' => &$this->attributes
            )
        );
        echo '<div class="col-xs-9">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">' . $this->title . '</h4>';
        echo '</div><div class="panel-body">';
        $this->render(12);
        echo '</div></div>';
        // Re-associate action box
        echo '<div class="venue-actions hiddeninitially">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">' . _('Re-associate Selected') . '</h4>';
        echo '</div><div class="panel-body">';
        echo '<form class="form-horizontal venue-reassociate-form" method="post" '
            . 'action="?node=venue&sub=reassociatemulti">';
        echo $this->_csrfInput();
        echo '<p class="text-muted">'
            . _('Add hosts on each venue subnet to its host group, '
            . 'and move storage nodes to its storage group.')
            . '</p>';
        echo '<input type="hidden" name="venueIDArray"/>';
        echo '<button type="submit" class="btn btn-info btn-block">'
            . '<i class="fa fa-refresh"></i> '
            . _('Re-associate') . '</button>';
        echo '</form></div></div>';
        // Delete action box
        echo '<div class="panel panel-warning">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">' . _('Delete Selected') . '</h4>';
        echo '</div><div class="panel-body">';
        echo '<form class="form-horizontal venue-deletemulti-form" method="post" '
            . 'action="?node=venue&sub=deletemulti">';
        echo $this->_csrfInput();
        echo '<div class="form-group"><label class="col-xs-6">'
            . _('Delete storage groups') . '</label>'
            . '<div class="col-xs-6"><input type="checkbox" name="delSG"/></div></div>';
        echo '<div class="form-group"><label class="col-xs-6">'
            . _('Delete host groups') . '</label>'
            . '<div class="col-xs-6"><input type="checkbox" name="delHG"/></div></div>';
        echo '<div class="form-group"><label class="col-xs-6">'
            . _('Delete hosts in groups') . '</label>'
            . '<div class="col-xs-6"><input type="checkbox" name="delHosts"/></div></div>';
        echo '<input type="hidden" name="venueIDArray"/>';
        echo '<button type="submit" class="btn btn-danger btn-block">'
            . '<i class="fa fa-trash"></i> '
            . _('Delete') . '</button>';
        echo '</form></div></div>';
        echo '</div></div>';
    }
    /**
     * Search/default page — shows venue listing.
     *
     * @return void
     */
    public function search()
    {
        $this->index();
    }
    /**
     * Search results page.
     *
     * @return void
     */
    public function searchPost()
    {
        $this->index();
    }
    /**
     * Create a new venue form.
     *
     * @return void
     */
    public function add()
    {
        $this->_emitCsrfMeta();
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->attributes,
            $this->templates
        );
        $this->title = _('New Venue');
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $this->attributes = array(
            array('class' => 'col-xs-4'),
            array('class' => 'col-xs-8 form-group'),
        );
        $description = filter_input(INPUT_POST, 'description');
        $subnet = filter_input(INPUT_POST, 'subnet');
        $storagegroupID = filter_input(INPUT_POST, 'storagegroupID');
        $hostgroupID = filter_input(INPUT_POST, 'hostgroupID');
        $sgbuild = self::getClass('StorageGroupManager')->buildSelectBox(
            $storagegroupID,
            'storagegroupID'
        );
        $grpbuild = self::getClass('GroupManager')->buildSelectBox(
            $hostgroupID,
            'hostgroupID'
        );
        $createSG = isset($_POST['createSG']) ? ' checked' : '';
        $createHG = isset($_POST['createHG']) ? ' checked' : '';
        $fields = array(
            '<label for="description">'
            . _('Description')
            . '</label>' => '<div class="input-group">'
            . '<textarea class="form-control" name="description" '
            . 'id="description">'
            . Initiator::e($description)
            . '</textarea>'
            . '</div>',
            '<label for="subnet">'
            . _('Subnet (CIDR)')
            . '</label>' => '<div class="input-group">'
            . '<input type="text" class="form-control" name="subnet" value="'
            . Initiator::e($subnet)
            . '" id="subnet" placeholder="10.81.20.0/22" required/>'
            . '</div>',
            '<label for="storagegroupID">'
            . _('Storage Group')
            . '</label>' => '<div id="sgSelectWrap" style="display:inline-block">'
            . $sgbuild . '</div>'
            . ' <label style="font-weight:normal;margin-left:10px">'
            . '<input type="checkbox" name="createSG" id="createSG"'
            . $createSG . '/> '
            . _('Create new')
            . '</label>',
            '<label for="hostgroupID">'
            . _('Host Group')
            . '</label>' => '<div id="hgSelectWrap" style="display:inline-block">'
            . $grpbuild . '</div>'
            . ' <label style="font-weight:normal;margin-left:10px">'
            . '<input type="checkbox" name="createHG" id="createHG"'
            . $createHG . '/> '
            . _('Create new')
            . '</label>',
            '<label for="add">'
            . _('Create New Venue')
            . '</label>' => '<button type="submit" name="add" id="add" '
            . 'class="btn btn-info btn-block">'
            . _('Add')
            . '</button>',
        );
        array_walk($fields, $this->fieldsToData);
        self::$HookManager->processEvent(
            'VENUE_ADD',
            array(
                'headerData' => &$this->headerData,
                'data' => &$this->data,
                'templates' => &$this->templates,
                'attributes' => &$this->attributes
            )
        );
        echo '<div class="col-xs-9">';
        echo '<form class="form-horizontal" method="post" action="'
            . $this->formAction
            . '" id="add">';
        echo $this->_csrfInput();
        // --- F1 Arcade venue picker card ---
        $this->_renderApiVenuePicker('create');
        // --- end picker card ---
        $this->indexDivDisplay();
        echo '</form>';
        echo '</div>';
        unset(
            $fields,
            $this->data,
            $this->form,
            $this->headerData,
            $this->attributes,
            $this->templates
        );
    }
    /**
     * Process new venue creation.
     *
     * @return void
     */
    public function addPost()
    {
        self::$HookManager->processEvent('VENUE_ADD_POST');
        $name = trim((string)filter_input(INPUT_POST, 'name'));
        $subnet = trim((string)filter_input(INPUT_POST, 'subnet'));
        $description = trim((string)filter_input(INPUT_POST, 'description'));
        $storagegroupID = (int)filter_input(INPUT_POST, 'storagegroupID');
        $hostgroupID = (int)filter_input(INPUT_POST, 'hostgroupID');
        $createSG = isset($_POST['createSG']);
        $createHG = isset($_POST['createHG']);
        $apiRegion = trim((string)(filter_input(INPUT_POST, 'apiRegion') ?? ''));
        $apiVenueRef = trim((string)(filter_input(INPUT_POST, 'apiVenueRef') ?? ''));
        $Venue = null;
        try {
            FOGCore::checkAuthAndCSRF();
            // Normalise API region first
            if ($apiRegion !== '' && !VenueApiClient::isValidRegion($apiRegion)) {
                $apiRegion = '';
                $apiVenueRef = '';
            }
            // Validate subnet up-front (no DB side-effects yet)
            if ($subnet === '' || strpos($subnet, '/') === false) {
                throw new VenueException(
                    _('Subnet must be in CIDR notation (e.g. 10.81.20.0/22)')
                );
            }
            // Fetch API venue data and derive canonical name BEFORE creating anything
            $apiVenueData = '';
            if ($apiVenueRef !== '' && $apiRegion !== '') {
                $apiVenueData = $this->_fetchApiVenueDataJson($apiRegion, $apiVenueRef);
                $decoded = json_decode($apiVenueData, true);
                if (!is_array($decoded)) {
                    throw new VenueException(_('Invalid API response format'));
                }
                $name = (string)($decoded['name'] ?? $name);
            }
            if ($name === '') {
                throw new VenueException(
                    _('Please select an F1 Arcade venue or enter a name.')
                );
            }
            if (!preg_match(self::VENUE_NAME_REGEX, $name)) {
                throw new VenueException(
                    _('Venue name contains invalid characters')
                );
            }
            $exists = self::getSubObjectIDs('Venue', array('name' => $name));
            if (count($exists) > 0) {
                throw new VenueException(
                    _('A venue with this name already exists')
                );
            }
            // All validation passed — now create supporting groups
            if ($createSG) {
                $sgName = ucfirst($name);
                $StorageGroup = self::getClass('StorageGroup')
                    ->set('name', $sgName)
                    ->set('description', sprintf('Auto-created for venue %s', $name));
                if (!$StorageGroup->save()) {
                    throw new VenueException(_('Failed to create storage group'));
                }
                $storagegroupID = $StorageGroup->get('id');
            }
            if ($createHG) {
                $hgName = ucfirst($name);
                $HostGroup = self::getClass('Group')
                    ->set('name', $hgName)
                    ->set('description', sprintf('Auto-created for venue %s', $name))
                    ->set('createdBy', self::$FOGUser->get('name'));
                if (!$HostGroup->save()) {
                    throw new VenueException(_('Failed to create host group'));
                }
                $hostgroupID = $HostGroup->get('id');
            }
            // Save the venue LAST so that on failure only orphan groups remain
            $Venue = self::getClass('Venue')
                ->set('name', $name)
                ->set('description', $description)
                ->set('subnet', $subnet)
                ->set('storagegroupID', $storagegroupID)
                ->set('hostgroupID', $hostgroupID)
                ->set('apiRegion', $apiRegion)
                ->set('apiVenueRef', $apiVenueRef)
                ->set('apiVenueData', $apiVenueData)
                ->set('createdBy', self::$FOGUser->get('name'));
            if (!$Venue->save()) {
                throw new VenueException(_('Add venue failed'));
            }
            $hook = 'VENUE_ADD_SUCCESS';
            $msg = json_encode(
                array(
                    'msg' => _('Venue created'),
                    'title' => _('Venue Create Success')
                )
            );
        } catch (Exception $e) {
            $hook = 'VENUE_ADD_FAIL';
            $msg = json_encode(
                array(
                    'error' => $e->getMessage(),
                    'title' => _('Venue Create Fail')
                )
            );
        }
        self::$HookManager->processEvent(
            $hook,
            array('Venue' => &$Venue)
        );
        unset($Venue);
        echo $msg;
        exit;
    }
    /**
     * Edit venue form.
     *
     * @return void
     */
    public function edit()
    {
        $this->_emitCsrfMeta();
        $this->title = sprintf('%s: %s', _('Edit'), $this->obj->get('name'));
        echo '<div class="col-xs-9 tab-content">';
        $this->_editGeneral();
        $this->_editConfig();
        $this->_editMembership();
        echo '</div>';
    }
    /**
     * General tab.
     *
     * @return void
     */
    private function _editGeneral()
    {
        unset($this->data, $this->headerData, $this->templates, $this->attributes);
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $this->attributes = array(
            array('class' => 'col-xs-4'),
            array('class' => 'col-xs-8 form-group'),
        );
        $sgbuild = self::getClass('StorageGroupManager')->buildSelectBox(
            $this->obj->get('storagegroupID'),
            'storagegroupID'
        );
        $grpbuild = self::getClass('GroupManager')->buildSelectBox(
            $this->obj->get('hostgroupID'),
            'hostgroupID'
        );
        // API venue association data
        $apiRegion = $this->obj->get('apiRegion') ?: '';
        $apiVenueRef = $this->obj->get('apiVenueRef') ?: '';
        $apiData = array();
        if ($apiVenueRef) {
            $decoded = json_decode($this->obj->get('apiVenueData') ?: '{}', true);
            if (is_array($decoded)) {
                $apiData = $decoded;
            }
        }
        $fields = array(
            '<label for="description">'
            . _('Description')
            . '</label>' => '<div class="input-group">'
            . '<textarea class="form-control" name="description" '
            . 'id="description">'
            . Initiator::e($this->obj->get('description'))
            . '</textarea>'
            . '</div>',
            '<label for="subnet">'
            . _('Subnet (CIDR)')
            . '</label>' => '<div class="input-group">'
            . '<input type="text" class="form-control" name="subnet" value="'
            . Initiator::e($this->obj->get('subnet'))
            . '" id="subnet" required/>'
            . '</div>',
            '<label for="storagegroupID">'
            . _('Storage Group')
            . '</label>' => $sgbuild,
            '<label for="hostgroupID">'
            . _('Host Group')
            . '</label>' => $grpbuild,
            '<label for="update">'
            . _('Update Venue')
            . '</label>' => '<button type="submit" name="update" id="update" '
            . 'class="btn btn-info btn-block">'
            . _('Update')
            . '</button>',
        );
        array_walk($fields, $this->fieldsToData);
        self::$HookManager->processEvent(
            'VENUE_EDIT',
            array(
                'headerData' => &$this->headerData,
                'data' => &$this->data,
                'templates' => &$this->templates,
                'attributes' => &$this->attributes,
                'Venue' => &$this->obj,
            )
        );
        echo '<div id="venue-gen" class="tab-pane fade in active">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">' . _('Venue General') . '</h4>';
        echo '</div><div class="panel-body">';
        echo '<form class="form-horizontal" method="post" action="'
            . $this->formAction . '&tab=venue-gen">';
        echo $this->_csrfInput();
        // --- F1 Arcade venue association card ---
        $this->_renderApiVenuePicker('edit', $apiRegion, $apiVenueRef, $apiData);
        // --- end association card ---
        $this->render(12);
        echo '</form></div></div></div>';
    }
    /**
     * Configuration tab — renders template-driven fields + custom overrides.
     *
     * @return void
     */
    private function _editConfig()
    {
        unset($this->data, $this->headerData, $this->templates, $this->attributes);
        $venueID = $this->obj->get('id');

        // Load all templates ordered by sort order
        $templates = self::getClass('VenueConfigTemplateManager')
            ->find(array(), 'AND', 'vctOrder', 'ASC');

        // Load existing venue config overrides keyed by key name
        $configs = self::getClass('VenueConfigManager')
            ->find(array('venueID' => $venueID));
        $overrides = array();
        foreach ((array)$configs as $config) {
            $overrides[$config->get('key')] = $config->get('value');
        }

        // Decode the API venue data for mapping defaults
        $apiVenueData = null;
        $apiJson = $this->obj->get('apiVenueData');
        if ($apiJson) {
            $apiVenueData = json_decode($apiJson, true);
        }

        echo '<div id="venue-config" class="tab-pane fade">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">' . _('Venue Configuration') . '</h4>';
        echo '</div><div class="panel-body">';

        // Sync config button (only if venue is associated with API)
        if ($this->obj->get('apiVenueRef')) {
            echo '<div style="margin-bottom:15px;text-align:right">';
            echo '<button type="button" class="btn btn-default btn-sm venue-sync-config" '
                . 'data-venue-id="' . (int)$venueID . '">'
                . '<i class="fa fa-refresh"></i> ' . _('Sync from API')
                . '</button>';
            echo '</div>';
        }

        // Template-driven fields
        if (!empty($templates)) {
            printf(
                '<form method="post" class="venue-config-form form-horizontal" action="%s&tab=venue-config">',
                $this->formAction
            );
            echo $this->_csrfInput();
            echo '<input type="hidden" name="templateConfig" value="1"/>';

            // Group templates by their group field
            $groups = array();
            foreach ((array)$templates as $tpl) {
                $group = $tpl->get('group') ?: '';
                $groups[$group][] = $tpl;
            }

            foreach ($groups as $groupName => $groupTemplates) {
                if ($groupName !== '') {
                    echo '<fieldset style="margin-bottom:20px">';
                    echo '<legend style="font-size:14px;font-weight:600;border-bottom:1px solid #ddd;padding-bottom:5px;margin-bottom:15px">'
                        . htmlspecialchars($groupName) . '</legend>';
                }
                foreach ($groupTemplates as $tpl) {
                    $key = $tpl->get('key');
                    $label = $tpl->get('label');
                    $type = $tpl->get('type');
                    $default = $tpl->get('default');
                    $options = $tpl->get('options');
                    $placeholder = $tpl->get('placeholder');
                    $required = (int)$tpl->get('required');
                    $desc = $tpl->get('description');
                    $validation = $tpl->get('validation');
                    $validationMsg = $tpl->get('validationMsg');
                    $apiMapping = $tpl->get('apiMapping');
                    // Resolve API-mapped default if venue is associated
                    if ($apiMapping && is_array($apiVenueData)) {
                        $mapped = VenueConfigMappingResolver::resolve($apiVenueData, $apiMapping);
                        if ($mapped !== null) {
                            $default = $mapped;
                        }
                    }
                    $currentValue = isset($overrides[$key]) ? $overrides[$key] : null;
                    // Decrypt password-type values for display
                    if ($type === 'password' && $currentValue !== null) {
                        $currentValue = VenueConfigCrypto::decrypt($currentValue);
                    }
                    $isDefault = ($currentValue === null);
                    $displayValue = $isDefault ? $default : $currentValue;

                    echo '<div class="form-group"'
                        . ($validation ? ' data-validation="' . htmlspecialchars($validation) . '"' : '')
                        . ($validationMsg ? ' data-validation-msg="' . htmlspecialchars($validationMsg) . '"' : '')
                        . '>';
                    echo '<label class="col-xs-3 control-label" for="tpl_' . htmlspecialchars($key) . '">';
                    echo htmlspecialchars($label);
                    if ($required) {
                        echo ' <span class="text-danger">*</span>';
                    }
                    echo '</label>';
                    echo '<div class="col-xs-6">';
                    echo self::_renderTemplateField($key, $type, $displayValue, $options, $required, $placeholder);
                    echo '</div>';
                    echo '<div class="col-xs-3">';
                    if ($desc) {
                        echo '<p class="help-block">' . htmlspecialchars($desc) . '</p>';
                    }
                    echo '</div></div>';
                }
                if ($groupName !== '') {
                    echo '</fieldset>';
                }
            }

            echo '<div class="form-group"><div class="col-xs-offset-3 col-xs-6">';
            echo '<button class="btn btn-info btn-block" type="submit">'
                . _('Save Configuration') . '</button>';
            echo '</div></div>';
            echo '</form>';
        } else {
            echo '<p class="text-muted">'
                . _('No config templates defined. ')
                . '<a href="?node=venue&sub=templates">' . _('Add templates') . '</a>'
                . _(' to define venue configuration fields.')
                . '</p>';
        }

        echo '</div></div></div>';
    }
    /**
     * Render a single template config row for the Config Management table.
     *
     * @param object $item VenueConfigTemplate instance.
     *
     * @return string
     */
    private static function _renderTplRow($item)
    {
        $id = $item->get('id');
        $desc = htmlspecialchars($item->get('description') ?? '');
        $group = htmlspecialchars($item->get('group') ?? '');
        $validation = htmlspecialchars($item->get('validation') ?? '');
        $validationMsg = htmlspecialchars($item->get('validationMsg') ?? '');
        $apiMapping = htmlspecialchars($item->get('apiMapping') ?? '');
        $html = '<tr class="venue-tpl-row" draggable="true" data-id="' . $id . '" '
            . 'data-key="' . htmlspecialchars($item->get('key')) . '" '
            . 'data-label="' . htmlspecialchars($item->get('label')) . '" '
            . 'data-type="' . htmlspecialchars($item->get('type')) . '" '
            . 'data-default="' . htmlspecialchars($item->get('default') ?? '') . '" '
            . 'data-placeholder="' . htmlspecialchars($item->get('placeholder') ?? '') . '" '
            . 'data-options="' . htmlspecialchars($item->get('options') ?? '') . '" '
            . 'data-required="' . (int)$item->get('required') . '" '
            . 'data-description="' . $desc . '" '
            . 'data-validation="' . $validation . '" '
            . 'data-validation-msg="' . $validationMsg . '" '
            . 'data-api-mapping="' . $apiMapping . '" '
            . 'data-group="' . $group . '" '
            . 'data-order="' . (int)$item->get('order') . '">';
        $html .= '<td class="venue-tpl-handle" style="cursor:move;color:#999;text-align:center;padding-left:20px">'
            . '<i class="fa fa-bars"></i></td>';
        $html .= '<td><code style="font-size:12px">'
            . htmlspecialchars($item->get('key')) . '</code></td>';
        $html .= '<td>' . htmlspecialchars($item->get('label'))
            . ($desc ? '<br><small class="text-muted">' . $desc . '</small>' : '')
            . '</td>';
        $html .= '<td><span class="label label-info">'
            . htmlspecialchars($item->get('type')) . '</span></td>';
        $html .= '<td class="text-center">' . ((int)$item->get('required')
            ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-minus text-muted"></i>') . '</td>';
        $html .= '<td>'
            . '<div class="btn-group">'
            . '<button class="btn btn-info btn-xs venue-tpl-edit" title="' . _('Edit') . '">'
            . '<i class="fa fa-pencil"></i></button>'
            . '<button class="btn btn-danger btn-xs venue-tpl-delete" title="' . _('Delete') . '">'
            . '<i class="fa fa-trash"></i></button>'
            . '</div></td>';
        $html .= '</tr>';
        echo $html;
    }

    /**
     * Render the appropriate HTML input for a template field type.
     *
     * @param string $key         The config key.
     * @param string $type        The field type.
     * @param string $value       The current value to display.
     * @param string $options     Pipe-delimited options (for select type).
     * @param int    $required    Whether the field is required.
     * @param string $placeholder Placeholder text for text-like fields.
     *
     * @return string
     */
    private static function _renderTemplateField($key, $type, $value, $options, $required, $placeholder = '')
    {
        $name = 'tpl_' . htmlspecialchars($key);
        $id = $name;
        $req = $required ? ' required' : '';
        $val = htmlspecialchars($value ?? '');
        $ph = $placeholder ? ' placeholder="' . htmlspecialchars($placeholder) . '"' : '';

        switch ($type) {
            case 'checkbox':
                $checked = ($value === '1' || $value === 'true') ? ' checked' : '';
                return '<input type="hidden" name="' . $name . '" value="0"/>'
                    . '<input type="checkbox" name="' . $name . '" id="' . $id
                    . '" value="1"' . $checked . '/>';

            case 'select':
                $opts = array_filter(explode('|', $options ?? ''));
                $html = '<select class="form-control" name="' . $name
                    . '" id="' . $id . '"' . $req . '>';
                foreach ($opts as $opt) {
                    $optVal = htmlspecialchars(trim($opt));
                    $sel = ($optVal === $val) ? ' selected' : '';
                    $html .= '<option value="' . $optVal . '"' . $sel . '>'
                        . $optVal . '</option>';
                }
                $html .= '</select>';
                return $html;

            case 'textarea':
                return '<textarea class="form-control" name="' . $name
                    . '" id="' . $id . '" rows="3"' . $ph . $req . '>'
                    . $val . '</textarea>';

            case 'password':
                return '<div class="input-group">'
                    . '<input type="password" class="form-control venue-password-field" name="'
                    . $name . '" id="' . $id . '" value="' . $val . '"' . $ph . $req . '/>'
                    . '<span class="input-group-btn">'
                    . '<button type="button" class="btn btn-default venue-toggle-password" '
                    . 'data-target="#' . $id . '">'
                    . '<i class="fa fa-eye"></i></button></span></div>';

            case 'number':
                return '<div class="input-group"><input type="number" class="form-control" name="'
                    . $name . '" id="' . $id . '" value="' . $val . '"' . $ph . $req . '/></div>';

            case 'url':
                $urlPh = $placeholder ? $ph : ' placeholder="https://"';
                return '<div class="input-group"><input type="url" class="form-control" name="'
                    . $name . '" id="' . $id . '" value="' . $val . '"'
                    . $urlPh . $req . '/></div>';

            case 'email':
                return '<div class="input-group"><input type="email" class="form-control" name="'
                    . $name . '" id="' . $id . '" value="' . $val . '"' . $ph . $req . '/></div>';

            case 'text':
            default:
                return '<div class="input-group"><input type="text" class="form-control" name="'
                    . $name . '" id="' . $id . '" value="' . $val . '"' . $ph . $req . '/></div>';
        }
    }
    /**
     * Helper: check if an IP falls within a CIDR subnet.
     *
     * @param string $ip   The IP address.
     * @param string $cidr The subnet in CIDR notation.
     *
     * @return bool
     */
    private static function _ipInSubnet($ip, $cidr)
    {
        if (empty($ip) || empty($cidr)) {
            return false;
        }
        $parts = explode('/', $cidr);
        if (count($parts) !== 2) {
            return false;
        }
        list($subnet, $mask) = $parts;
        $mask = (int)$mask;
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }
        $maskLong = ~((1 << (32 - $mask)) - 1);
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
    /**
     * Membership tab — show hosts and storage nodes on subnet.
     *
     * @return void
     */
    private function _editMembership()
    {
        $subnet = $this->obj->get('subnet');
        $hostgroupID = $this->obj->get('hostgroupID');
        $storagegroupID = $this->obj->get('storagegroupID');
        // Find hosts on subnet
        Route::listem('host');
        $allHosts = json_decode(Route::getData());
        $hostsOnSubnet = array();
        $hostsInGroup = array();
        foreach ((array)($allHosts->hosts ?? array()) as $host) {
            $ip = $host->ip ?? '';
            if (self::_ipInSubnet($ip, $subnet)) {
                $hostsOnSubnet[$host->id] = $host;
            }
        }
        // Get current host group members
        if ($hostgroupID) {
            $HG = self::getClass('Group', $hostgroupID);
            if ($HG->isValid()) {
                $hostsInGroup = (array)$HG->get('hosts');
            }
        }
        // Find storage nodes on subnet
        Route::listem('storagenode');
        $allNodes = json_decode(Route::getData());
        $nodesOnSubnet = array();
        $nodesInGroup = array();
        foreach ((array)($allNodes->storagenodes ?? array()) as $sn) {
            $ip = $sn->ip ?? '';
            if (self::_ipInSubnet($ip, $subnet)) {
                $nodesOnSubnet[$sn->id] = $sn;
            }
        }
        // Get current storage group members
        if ($storagegroupID) {
            $nodesInGroup = self::getSubObjectIDs(
                'StorageNode',
                array('storagegroupID' => $storagegroupID)
            );
        }
        // Render membership tab
        echo '<div id="venue-membership" class="tab-pane fade">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">' . _('Hosts on Subnet') . ' (' . $subnet . ')</h4>';
        echo '</div><div class="panel-body">';
        if (empty($hostsOnSubnet)) {
            echo '<p class="text-muted">' . _('No hosts found on this subnet.') . '</p>';
        } else {
            echo '<table class="table table-condensed table-hover">';
            echo '<thead><tr><th>' . _('Host') . '</th><th>' . _('IP') . '</th>'
                . '<th>' . _('In Group') . '</th></tr></thead><tbody>';
            foreach ($hostsOnSubnet as $host) {
                $inGroup = in_array($host->id, $hostsInGroup);
                $badge = $inGroup
                    ? '<span class="label label-success">' . _('Yes') . '</span>'
                    : '<span class="label label-default">' . _('No') . '</span>';
                echo '<tr><td>' . htmlspecialchars($host->name) . '</td>'
                    . '<td>' . htmlspecialchars($host->ip) . '</td>'
                    . '<td>' . $badge . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div></div>';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">' . _('Storage Nodes on Subnet') . '</h4>';
        echo '</div><div class="panel-body">';
        if (empty($nodesOnSubnet)) {
            echo '<p class="text-muted">' . _('No storage nodes found on this subnet.') . '</p>';
        } else {
            echo '<table class="table table-condensed table-hover">';
            echo '<thead><tr><th>' . _('Node') . '</th><th>' . _('IP') . '</th>'
                . '<th>' . _('In Group') . '</th></tr></thead><tbody>';
            foreach ($nodesOnSubnet as $sn) {
                $inGroup = in_array($sn->id, $nodesInGroup);
                $badge = $inGroup
                    ? '<span class="label label-success">' . _('Yes') . '</span>'
                    : '<span class="label label-default">' . _('No') . '</span>';
                echo '<tr><td>' . htmlspecialchars($sn->name) . '</td>'
                    . '<td>' . htmlspecialchars($sn->ip) . '</td>'
                    . '<td>' . $badge . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div></div>';
        // Re-associate button
        $hostsToAdd = array_diff(array_keys($hostsOnSubnet), $hostsInGroup);
        $nodesToMove = array_diff(array_keys($nodesOnSubnet), $nodesInGroup);
        $hasChanges = !empty($hostsToAdd) || !empty($nodesToMove);
        echo '<form method="post" class="venue-membership-form" action="' . $this->formAction . '&tab=venue-membership">';
        echo $this->_csrfInput();
        echo '<div class="form-group">';
        if ($hasChanges) {
            $summary = array();
            if (!empty($hostsToAdd)) {
                $summary[] = sprintf(_('%d host(s) to add to group'), count($hostsToAdd));
            }
            if (!empty($nodesToMove)) {
                $summary[] = sprintf(_('%d storage node(s) to move to group'), count($nodesToMove));
            }
            echo '<p class="text-info">' . implode(', ', $summary) . '</p>';
            echo '<button type="submit" name="reassociate" '
                . 'class="btn btn-info btn-block">'
                . '<i class="fa fa-refresh"></i> '
                . _('Re-associate to Venue Groups')
                . '</button>';
        } else {
            echo '<p class="text-success"><i class="fa fa-check"></i> '
                . _('All hosts and storage nodes on this subnet are already in the correct groups.')
                . '</p>';
        }
        echo '</div></form></div>';
    }
    /**
     * Process edit form.
     *
     * @return void
     */
    public function editPost()
    {
        self::$HookManager->processEvent(
            'VENUE_EDIT_POST',
            array('Venue' => &$this->obj)
        );
        $tab = filter_input(INPUT_GET, 'tab') ?: 'venue-gen';
        $successMsg = _('Venue updated!');
        try {
            FOGCore::checkAuthAndCSRF();
            switch ($tab) {
                case 'venue-gen':
                    $this->_editGeneralPost();
                    break;
                case 'venue-config':
                    $this->_editConfigPost();
                    break;
                case 'venue-membership':
                    $successMsg = $this->_editMembershipPost();
                    break;
            }
            $hook = 'VENUE_EDIT_SUCCESS';
            $msg = json_encode(
                array(
                    'msg' => $successMsg,
                    'title' => _('Venue Update Success')
                )
            );
        } catch (Exception $e) {
            $hook = 'VENUE_EDIT_FAIL';
            $msg = json_encode(
                array(
                    'error' => $e->getMessage(),
                    'title' => _('Venue Update Fail')
                )
            );
        }
        self::$HookManager->processEvent(
            $hook,
            array('Venue' => &$this->obj)
        );
        echo $msg;
        exit;
    }
    /**
     * Process general tab update.
     *
     * @return void
     */
    private function _editGeneralPost()
    {
        $name = trim((string)filter_input(INPUT_POST, 'name'));
        $subnet = trim((string)filter_input(INPUT_POST, 'subnet'));
        $description = trim((string)filter_input(INPUT_POST, 'description'));
        $storagegroupID = (int)filter_input(INPUT_POST, 'storagegroupID');
        $hostgroupID = (int)filter_input(INPUT_POST, 'hostgroupID');
        $apiRegion = trim((string)(filter_input(INPUT_POST, 'apiRegion') ?? ''));
        $apiVenueRef = trim((string)(filter_input(INPUT_POST, 'apiVenueRef') ?? ''));
        if ($subnet === '' || strpos($subnet, '/') === false) {
            throw new VenueException(_('Subnet must be in CIDR notation'));
        }
        // Validate API region if set
        if ($apiRegion !== '' && !VenueApiClient::isValidRegion($apiRegion)) {
            $apiRegion = '';
            $apiVenueRef = '';
        }
        // Fetch and cache API venue data if association changed
        $apiVenueData = $this->obj->get('apiVenueData') ?: '';
        if ($apiVenueRef !== ''
            && ($apiVenueRef !== $this->obj->get('apiVenueRef')
                || $apiRegion !== $this->obj->get('apiRegion'))
        ) {
            $apiVenueData = $this->_fetchApiVenueDataJson($apiRegion, $apiVenueRef);
        } elseif ($apiVenueRef === '') {
            $apiVenueData = '';
        }
        // Derive venue name from API data if associated
        if ($apiVenueData !== '') {
            $decoded = json_decode($apiVenueData, true);
            if (!is_array($decoded)) {
                throw new VenueException(_('Invalid API response format'));
            }
            $name = (string)($decoded['name'] ?? $name);
        }
        if ($name === '' || !preg_match(self::VENUE_NAME_REGEX, $name)) {
            throw new VenueException(
                _('Venue name contains invalid characters')
            );
        }
        $this->obj
            ->set('name', $name)
            ->set('description', $description)
            ->set('subnet', $subnet)
            ->set('storagegroupID', $storagegroupID)
            ->set('hostgroupID', $hostgroupID)
            ->set('apiRegion', $apiRegion)
            ->set('apiVenueRef', $apiVenueRef)
            ->set('apiVenueData', $apiVenueData);
        if (!$this->obj->save()) {
            throw new VenueException(_('Update venue failed'));
        }
    }
    /**
     * Fetch a single venue's data from the F1 Arcade API and return
     * the canonical JSON string we store in `apiVenueData`.
     *
     * @param string $region   The region code.
     * @param string $venueRef The venue reference id.
     *
     * @throws VenueException On any API failure or invalid region.
     *
     * @return string JSON-encoded venue data.
     */
    private function _fetchApiVenueDataJson($region, $venueRef)
    {
        $venue = VenueApiClient::findVenue($region, $venueRef);
        $encoded = json_encode($venue);
        if ($encoded === false) {
            throw new VenueException(_('Failed to encode venue data'));
        }
        return $encoded;
    }
    /**
     * Emit the CSRF meta tag for AJAX/form auto-attach helpers.
     *
     * @return void
     */
    private function _emitCsrfMeta()
    {
        echo '<meta name="csrf-token" content="'
            . htmlspecialchars(CSRF::token(), ENT_QUOTES, 'UTF-8')
            . '"/>';
    }
    /**
     * Render a hidden CSRF input for plain form POSTs.
     *
     * @return string
     */
    private function _csrfInput()
    {
        return '<input type="hidden" name="_csrf" value="'
            . htmlspecialchars(CSRF::token(), ENT_QUOTES, 'UTF-8')
            . '"/>';
    }
    /**
     * Emit the available API mapping paths as a JSON blob for the
     * template editor JS to consume.
     *
     * @return void
     */
    private function _emitApiMappingPathsJson()
    {
        echo '<script type="application/json" id="venue-api-mapping-paths">'
            . json_encode(VenueConfigMappingResolver::availablePaths())
            . '</script>';
    }
    /**
     * Standard JSON error responder for AJAX endpoints.
     *
     * @param Exception $e     The thrown exception.
     * @param string    $title Optional title for the error envelope.
     *
     * @return void
     */
    private function _ajaxJsonError(Exception $e, $title = '')
    {
        header('Content-Type: application/json');
        echo json_encode(
            array(
                'error' => $e->getMessage(),
                'title' => $title !== '' ? $title : _('Error'),
            )
        );
        exit;
    }
    /**
     * Render the F1 Arcade venue picker card used by both the
     * create form and the edit form.
     *
     * @param string $context     Either 'create' or 'edit'.
     * @param string $apiRegion   Current api region (edit only).
     * @param string $apiVenueRef Current api venue ref (edit only).
     * @param array  $apiData     Decoded apiVenueData (edit only).
     *
     * @return void
     */
    private function _renderApiVenuePicker(
        $context,
        $apiRegion = '',
        $apiVenueRef = '',
        array $apiData = array()
    ) {
        $apiVenueName = (string)($apiData['name'] ?? '');
        $apiVenueSlug = (string)($apiData['reference'] ?? '');
        $apiVenueCity = (string)($apiData['address']['city'] ?? '');
        $hasAssoc = ($context === 'edit' && $apiVenueRef !== '');

        echo '<div class="venue-api-assoc" data-region="'
            . htmlspecialchars($apiRegion, ENT_QUOTES, 'UTF-8')
            . '" data-venue-id="'
            . htmlspecialchars($apiVenueRef, ENT_QUOTES, 'UTF-8') . '"'
            . ' style="border:1px solid ' . ($hasAssoc ? '#3c763d' : '#bce8f1')
            . ';border-radius:4px;padding:14px 18px;margin-bottom:20px;background:'
            . ($hasAssoc ? '#dff0d8' : '#d9edf7') . '">';

        if ($hasAssoc) {
            if (strpos($apiRegion, 'dev') !== false) {
                $regionLabel = strtoupper(str_replace('-dev', '', $apiRegion)) . ' DEV';
            } else {
                $regionLabel = strtoupper($apiRegion);
            }
            echo '<div style="display:flex;align-items:center;gap:12px">';
            echo '<div style="flex:1">';
            echo '<span class="label label-success" style="font-size:11px">'
                . htmlspecialchars($regionLabel, ENT_QUOTES, 'UTF-8') . '</span> ';
            echo '<strong style="font-size:16px">'
                . htmlspecialchars($apiVenueName, ENT_QUOTES, 'UTF-8') . '</strong>';
            if ($apiVenueSlug !== '') {
                echo ' <code style="font-size:11px;color:#666">'
                    . htmlspecialchars($apiVenueSlug, ENT_QUOTES, 'UTF-8') . '</code>';
            }
            if ($apiVenueCity !== '') {
                echo '<br><small class="text-muted">'
                    . htmlspecialchars($apiVenueCity, ENT_QUOTES, 'UTF-8') . '</small>';
            }
            echo '</div>';
            echo '<button type="button" class="btn btn-default btn-sm venue-api-pick">'
                . '<i class="fa fa-pencil"></i> ' . _('Change') . '</button>';
            echo '</div>';
        } else {
            $blurb = ($context === 'edit')
                ? _('Link this FOG venue to an F1 Arcade venue to auto-populate config defaults')
                : _('Select an F1 Arcade venue to auto-set the name and config defaults');
            echo '<div style="text-align:center">';
            echo '<p class="text-muted" style="margin:0 0 8px"><i class="fa fa-globe"></i> '
                . $blurb . '</p>';
            echo '<button type="button" class="btn btn-info btn-sm venue-api-pick">'
                . '<i class="fa fa-link"></i> ' . _('Select F1 Arcade Venue') . '</button>';
            echo '</div>';
        }
        echo '<input type="hidden" name="apiRegion" value="'
            . htmlspecialchars($apiRegion, ENT_QUOTES, 'UTF-8') . '"/>';
        echo '<input type="hidden" name="apiVenueRef" value="'
            . htmlspecialchars($apiVenueRef, ENT_QUOTES, 'UTF-8') . '"/>';
        echo '</div>';
    }
    /**
     * Process config tab — save template values or add custom key-value pair.
     *
     * @return void
     */
    private function _editConfigPost()
    {
        $isTemplateSubmit = filter_input(INPUT_POST, 'templateConfig');
        if ($isTemplateSubmit) {
            $this->_saveTemplateConfig();
            return;
        }
        // Legacy/custom config add
        $key = strtoupper(trim((string)filter_input(INPUT_POST, 'configKey')));
        $value = trim((string)filter_input(INPUT_POST, 'configValue'));
        if ($key === '') {
            throw new VenueException(_('Config key is required'));
        }
        if (!preg_match(self::CONFIG_KEY_REGEX, $key)) {
            throw new VenueException(
                _('Config key must be uppercase alphanumeric with underscores')
            );
        }
        if (!preg_match(self::CONFIG_VALUE_REGEX, $value)) {
            throw new VenueException(_('Config value contains invalid characters'));
        }
        $existing = self::getSubObjectIDs(
            'VenueConfig',
            array('venueID' => $this->obj->get('id'), 'key' => $key)
        );
        if (count($existing) > 0) {
            $Config = self::getClass('VenueConfig', current($existing));
            $Config->set('value', $value);
        } else {
            $Config = self::getClass('VenueConfig')
                ->set('venueID', $this->obj->get('id'))
                ->set('key', $key)
                ->set('value', $value);
        }
        if (!$Config->save()) {
            throw new VenueException(_('Failed to save config'));
        }
    }
    /**
     * Save template-driven config values for a venue.
     *
     * @return void
     */
    private function _saveTemplateConfig()
    {
        $venueID = $this->obj->get('id');
        $templates = self::getClass('VenueConfigTemplateManager')
            ->find(array(), 'AND', 'vctOrder', 'ASC');
        foreach ((array)$templates as $tpl) {
            $key = $tpl->get('key');
            $type = $tpl->get('type');
            $default = $tpl->get('default');
            $required = (int)$tpl->get('required');
            $postKey = 'tpl_' . $key;
            $value = isset($_POST[$postKey]) ? $_POST[$postKey] : null;

            // For checkbox, value is either '1' or '0'
            if ($type === 'checkbox') {
                $value = ($value === '1') ? '1' : '0';
            } else {
                $value = trim($value ?? '');
            }

            // Validate required fields
            if ($required && $value === '' && ($default === '' || $default === null)) {
                throw new VenueException(
                    sprintf(_('%s is required'), $tpl->get('label'))
                );
            }

            // Regex validation (skip empty non-required fields)
            $pattern = $tpl->get('validation');
            if ($pattern && $value !== '') {
                $this->_validateTemplateValue($tpl, $pattern, $value);
            }

            // If value matches default, remove any override (keep DB clean)
            $existing = self::getSubObjectIDs(
                'VenueConfig',
                array('venueID' => $venueID, 'key' => $key)
            );
            if ($value === $default || ($value === '' && $type !== 'checkbox')) {
                // Remove override if it exists — will fall back to default
                if (count($existing) > 0) {
                    $Config = self::getClass('VenueConfig', current($existing));
                    if (!$Config->destroy()) {
                        throw new VenueException(
                            sprintf(_('Failed to remove override for %s'), $key)
                        );
                    }
                }
            } else {
                // Encrypt password-type values before storage
                $storeValue = $value;
                if ($type === 'password' && $storeValue !== '') {
                    $storeValue = VenueConfigCrypto::encrypt($storeValue);
                }
                // Save override
                if (count($existing) > 0) {
                    $Config = self::getClass('VenueConfig', current($existing));
                    $Config->set('value', $storeValue);
                } else {
                    $Config = self::getClass('VenueConfig')
                        ->set('venueID', $venueID)
                        ->set('key', $key)
                        ->set('value', $storeValue);
                }
                if (!$Config->save()) {
                    throw new VenueException(
                        sprintf(_('Failed to save config for %s'), $key)
                    );
                }
            }
        }
    }
    /**
     * Run regex validation for a template-driven config field. Safely
     * detects malformed patterns and surfaces a user-friendly error.
     *
     * @param object $tpl     The VenueConfigTemplate instance.
     * @param string $pattern Raw validation pattern from the template.
     * @param string $value   The value to validate.
     *
     * @throws VenueException On invalid pattern or match failure.
     *
     * @return void
     */
    private function _validateTemplateValue($tpl, $pattern, $value)
    {
        $compiled = '/' . str_replace('/', '\\/', $pattern) . '/u';
        $result = @preg_match($compiled, $value);
        if ($result === false) {
            throw new VenueException(
                sprintf(
                    _('Invalid validation pattern for field: %s'),
                    $tpl->get('label')
                )
            );
        }
        if ($result === 0) {
            $msg = $tpl->get('validationMsg')
                ?: sprintf(_('%s has an invalid format'), $tpl->get('label'));
            throw new VenueException($msg);
        }
    }
    /**
     * Process membership re-association.
     *
     * @return void
     */
    private function _editMembershipPost()
    {
        $subnet = $this->obj->get('subnet');
        $hostgroupID = $this->obj->get('hostgroupID');
        $storagegroupID = $this->obj->get('storagegroupID');
        $hostsAdded = 0;
        $nodesMoved = 0;
        // Re-associate hosts to host group
        if ($hostgroupID) {
            $HG = self::getClass('Group', $hostgroupID);
            if ($HG->isValid()) {
                $currentMembers = (array)$HG->get('hosts');
                Route::listem('host');
                $allHosts = json_decode(Route::getData());
                $hostsToAdd = array();
                foreach ((array)($allHosts->hosts ?? array()) as $host) {
                    $ip = $host->ip ?? '';
                    if (self::_ipInSubnet($ip, $subnet)
                        && !in_array($host->id, $currentMembers)
                    ) {
                        $hostsToAdd[] = $host->id;
                    }
                }
                if (!empty($hostsToAdd)) {
                    $allMembers = array_merge($currentMembers, $hostsToAdd);
                    $HG->addHost($allMembers);
                    if (!$HG->save()) {
                        throw new VenueException(_('Failed to update host group'));
                    }
                    $hostsAdded = count($hostsToAdd);
                }
            }
        }
        // Re-associate storage nodes to storage group
        if ($storagegroupID) {
            Route::listem('storagenode');
            $allNodes = json_decode(Route::getData());
            foreach ((array)($allNodes->storagenodes ?? array()) as $sn) {
                $ip = $sn->ip ?? '';
                if (self::_ipInSubnet($ip, $subnet)
                    && $sn->storagegroupID != $storagegroupID
                ) {
                    $Node = self::getClass('StorageNode', $sn->id);
                    if ($Node->isValid()) {
                        $Node->set('storagegroupID', $storagegroupID);
                        if ($Node->save()) {
                            $nodesMoved++;
                        }
                    }
                }
            }
        }
        if ($hostsAdded === 0 && $nodesMoved === 0) {
            throw new VenueException(_('Nothing to re-associate. All items are already in the correct groups.'));
        }
        $parts = array();
        if ($hostsAdded > 0) {
            $parts[] = sprintf(_('%d host(s) added to group'), $hostsAdded);
        }
        if ($nodesMoved > 0) {
            $parts[] = sprintf(_('%d storage node(s) moved to group'), $nodesMoved);
        }
        self::$HookManager->processEvent(
            'VENUE_REASSOCIATE_SUCCESS',
            array('Venue' => &$this->obj)
        );
        return implode(', ', $parts);
    }
    /**
     * Display delete confirmation with cascade options.
     *
     * @return void
     */
    public function delete()
    {
        $this->_emitCsrfMeta();
        $this->title = sprintf(
            '%s: %s',
            _('Remove Venue'),
            $this->obj->get('name')
        );
        unset($this->headerData);
        $this->attributes = array(
            array('class' => 'col-xs-4'),
            array('class' => 'col-xs-8 form-group'),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $fields = array(
            '<label for="delSG">'
            . _('Delete storage group')
            . '</label>' => '<div class="input-group checkbox">'
            . '<input type="checkbox" name="delSG" id="delSG"/>'
            . '</div>',
            '<label for="delHG">'
            . _('Delete host group')
            . '</label>' => '<div class="input-group checkbox">'
            . '<input type="checkbox" name="delHG" id="delHG"/>'
            . '</div>',
            '<label for="delHosts">'
            . _('Delete hosts in the group')
            . '</label>' => '<div class="input-group checkbox">'
            . '<input type="checkbox" name="delHosts" id="delHosts"/>'
            . '</div>',
            '<label for="confirmDelete">'
            . $this->title
            . '</label>' => '<input type="hidden" name="remitems[]" value="'
            . Initiator::e($this->obj->get('id'))
            . '"/>'
            . '<button type="submit" name="confirmDelete" id="confirmDelete" '
            . 'class="btn btn-danger btn-block">'
            . _('Delete')
            . '</button>',
        );
        array_walk($fields, $this->fieldsToData);
        self::$HookManager->processEvent(
            'VENUE_DEL',
            array(
                'data' => &$this->data,
                'headerData' => &$this->headerData,
                'templates' => &$this->templates,
                'attributes' => &$this->attributes,
                'Venue' => &$this->obj,
            )
        );
        echo '<div class="col-xs-9">';
        echo '<div class="panel panel-warning">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">' . $this->title . '</h4>';
        echo '</div>';
        echo '<div class="panel-body">';
        echo '<form class="form-horizontal venue-delete-form" method="post" action="'
            . $this->formAction . '">';
        echo $this->_csrfInput();
        $this->render(12);
        echo '</form>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Process venue deletion with optional cascade.
     *
     * @return void
     */
    public function deletePost()
    {
        self::$HookManager->processEvent(
            'VENUE_DEL_POST',
            array('Venue' => &$this->obj)
        );
        try {
            FOGCore::checkAuthAndCSRF();
            $delSG = isset($_POST['delSG']);
            $delHG = isset($_POST['delHG']);
            $delHosts = isset($_POST['delHosts']);
            $storagegroupID = $this->obj->get('storagegroupID');
            $hostgroupID = $this->obj->get('hostgroupID');
            if (!$this->obj->destroy()) {
                throw new VenueException(_('Failed to delete venue'));
            }
            if ($delHosts && $hostgroupID) {
                $HostGroup = self::getClass('Group', $hostgroupID);
                if ($HostGroup->isValid()) {
                    $hostIDs = $HostGroup->get('hosts');
                    if (!empty($hostIDs)) {
                        self::getClass('HostManager')
                            ->destroy(array('id' => $hostIDs));
                    }
                }
            }
            if ($delHG && $hostgroupID) {
                $HostGroup = self::getClass('Group', $hostgroupID);
                if ($HostGroup->isValid()) {
                    $HostGroup->destroy();
                }
            }
            if ($delSG && $storagegroupID) {
                $StorageGroup = self::getClass('StorageGroup', $storagegroupID);
                if ($StorageGroup->isValid()) {
                    $StorageGroup->destroy();
                }
            }
            echo json_encode(
                array(
                    'msg' => _('Venue deleted'),
                    'title' => _('Delete Success')
                )
            );
        } catch (Exception $e) {
            echo json_encode(
                array(
                    'error' => $e->getMessage(),
                    'title' => _('Delete Fail')
                )
            );
        }
        exit;
    }
    /**
     * Multi-delete venues with cascade options.
     *
     * @return void
     */
    public function deletemulti()
    {
        $this->_emitCsrfMeta();
        $this->title = _('Delete Selected Venues');
        unset($this->headerData);
        $this->attributes = array(
            array('class' => 'col-xs-4'),
            array('class' => 'col-xs-8 form-group'),
        );
        $this->templates = array('${field}', '${input}');
        $venueIDs = filter_input(INPUT_POST, 'venueIDArray');
        if (empty($venueIDs)) {
            echo '<div class="col-xs-9"><p>' . _('No venues selected.') . '</p></div>';
            return;
        }
        $ids = array_filter(array_map('intval', explode(',', $venueIDs)));
        $names = array();
        foreach ($ids as $vid) {
            $V = self::getClass('Venue', $vid);
            if ($V->isValid()) {
                $names[] = $V->get('name');
            }
        }
        $fields = array(
            _('Selected Venues') => '<span class="form-control-static">'
                . implode(', ', array_map('htmlspecialchars', $names))
                . '</span>',
            '<label for="delSG">'
            . _('Delete storage groups')
            . '</label>' => '<div class="input-group checkbox">'
            . '<input type="checkbox" name="delSG" id="delSG"/>'
            . '</div>',
            '<label for="delHG">'
            . _('Delete host groups')
            . '</label>' => '<div class="input-group checkbox">'
            . '<input type="checkbox" name="delHG" id="delHG"/>'
            . '</div>',
            '<label for="delHosts">'
            . _('Delete hosts in the groups')
            . '</label>' => '<div class="input-group checkbox">'
            . '<input type="checkbox" name="delHosts" id="delHosts"/>'
            . '</div>',
            '<label for="confirmdel">'
            . _('Confirm Delete')
            . '</label>' => '<input type="hidden" name="venueIDArray" value="'
            . htmlspecialchars($venueIDs, ENT_QUOTES) . '"/>'
            . '<button type="submit" name="confirmdel" id="confirmdel" '
            . 'class="btn btn-danger btn-block">'
            . _('Delete')
            . '</button>',
        );
        array_walk($fields, $this->fieldsToData);
        echo '<div class="col-xs-9">';
        echo '<div class="panel panel-warning">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">' . $this->title . '</h4>';
        echo '</div><div class="panel-body">';
        echo '<form class="form-horizontal" method="post" action="'
            . '?node=venue&sub=deletemulti">';
        echo $this->_csrfInput();
        $this->render(12);
        echo '</form></div></div></div>';
    }
    /**
     * Process multi-delete.
     *
     * @return void
     */
    public function deletemultiPost()
    {
        $venueIDs = filter_input(INPUT_POST, 'venueIDArray');
        $ids = array_filter(array_map('intval', explode(',', $venueIDs)));
        $delSG = isset($_POST['delSG']);
        $delHG = isset($_POST['delHG']);
        $delHosts = isset($_POST['delHosts']);
        $deleted = 0;
        try {
            FOGCore::checkAuthAndCSRF();
            foreach ($ids as $vid) {
                $Venue = self::getClass('Venue', $vid);
                if (!$Venue->isValid()) {
                    continue;
                }
                $storagegroupID = $Venue->get('storagegroupID');
                $hostgroupID = $Venue->get('hostgroupID');
                if (!$Venue->destroy()) {
                    continue;
                }
                $deleted++;
                if ($delHosts && $hostgroupID) {
                    $HG = self::getClass('Group', $hostgroupID);
                    if ($HG->isValid()) {
                        $hostIDs = $HG->get('hosts');
                        if (!empty($hostIDs)) {
                            self::getClass('HostManager')
                                ->destroy(array('id' => $hostIDs));
                        }
                    }
                }
                if ($delHG && $hostgroupID) {
                    $HG = self::getClass('Group', $hostgroupID);
                    if ($HG->isValid()) {
                        $HG->destroy();
                    }
                }
                if ($delSG && $storagegroupID) {
                    $SG = self::getClass('StorageGroup', $storagegroupID);
                    if ($SG->isValid()) {
                        $SG->destroy();
                    }
                }
            }
            if ($deleted === 0) {
                throw new VenueException(_('No venues were deleted'));
            }
            echo json_encode(
                array(
                    'msg' => sprintf(_('%d venue(s) deleted'), $deleted),
                    'title' => _('Delete Success')
                )
            );
        } catch (Exception $e) {
            echo json_encode(
                array(
                    'error' => $e->getMessage(),
                    'title' => _('Delete Fail')
                )
            );
        }
        exit;
    }
    /**
     * Config Management page — inline-editable table.
     *
     * @return void
     */
    public function templates()
    {
        $this->_emitCsrfMeta();
        $this->title = _('Config Management');
        unset($this->data, $this->headerData, $this->templates, $this->attributes);

        $items = self::getClass('VenueConfigTemplateManager')
            ->find(array(), 'AND', 'vctOrder', 'ASC');

        $types = VenueConfigTemplate::getValidTypes();
        $typeOptions = '';
        foreach ($types as $t) {
            $typeOptions .= '<option value="' . $t . '">' . ucfirst($t) . '</option>';
        }

        echo '<div class="col-xs-9">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">' . $this->title . '</h4>';
        echo '</div>';

        // Hidden type options template for JS
        echo '<script type="text/template" id="tpl-type-options">' . $typeOptions . '</script>';
        $this->_emitApiMappingPathsJson();

        echo '<table class="table table-hover" id="venue-tpl-table">';
        echo '<thead><tr>';
        echo '<th style="width:40px"></th>';
        echo '<th style="width:180px">' . _('Key') . '</th>';
        echo '<th>' . _('Label') . '</th>';
        echo '<th style="width:100px">' . _('Type') . '</th>';
        echo '<th style="width:50px" class="text-center">' . _('Req') . '</th>';
        echo '<th style="width:90px">' . _('Actions') . '</th>';
        echo '</tr></thead><tbody>';

        // Group items by their group field, preserving order
        $groups = array();
        $ungrouped = array();
        foreach ((array)$items as $item) {
            $g = $item->get('group') ?: '';
            if ($g === '') {
                $ungrouped[] = $item;
            } else {
                $groups[$g][] = $item;
            }
        }

        // Render ungrouped items first
        foreach ($ungrouped as $item) {
            echo self::_renderTplRow($item);
        }

        // Render each group with a header row
        foreach ($groups as $groupName => $groupItems) {
            echo '<tr class="venue-tpl-group-header" draggable="true" data-group="'
                . htmlspecialchars($groupName) . '">';
            echo '<td style="cursor:move;color:#999;text-align:center">'
                . '<i class="fa fa-bars"></i></td>';
            echo '<td colspan="4" style="background:#eaf4fb;font-weight:600;padding:8px 12px">'
                . '<i class="fa fa-folder-open-o text-info"></i> '
                . htmlspecialchars($groupName) . '</td>';
            echo '<td style="background:#eaf4fb">'
                . '<div class="btn-group">'
                . '<button class="btn btn-default btn-xs venue-tpl-group-edit" title="' . _('Rename') . '">'
                . '<i class="fa fa-pencil"></i></button>'
                . '<button class="btn btn-danger btn-xs venue-tpl-group-delete" title="' . _('Remove Group') . '">'
                . '<i class="fa fa-trash"></i></button>'
                . '</div></td>';
            echo '</tr>';
            foreach ($groupItems as $item) {
                echo self::_renderTplRow($item);
            }
        }

        echo '</tbody></table>';
        echo '<div class="panel-footer">';
        echo '<button type="button" class="btn btn-success btn-sm" id="venue-tpl-add">'
            . '<i class="fa fa-plus"></i> ' . _('Add Config') . '</button> ';
        echo '<button type="button" class="btn btn-info btn-sm" id="venue-tpl-add-group">'
            . '<i class="fa fa-folder-o"></i> ' . _('Add Group') . '</button> ';
        echo '<div class="btn-group pull-right">';
        echo '<button type="button" class="btn btn-default btn-sm" id="venue-tpl-reseed" '
            . 'title="' . _('Insert any missing default templates without touching existing rows') . '">'
            . '<i class="fa fa-magic"></i> ' . _('Add Missing Defaults') . '</button>';
        echo '<button type="button" class="btn btn-warning btn-sm" id="venue-tpl-reseed-overwrite" '
            . 'title="' . _('Reset every default template to the canonical values, overwriting any local edits') . '">'
            . '<i class="fa fa-refresh"></i> ' . _('Reset All Defaults') . '</button>';
        echo '</div>';
        echo '</div></div></div>';
    }
    /**
     * Process Config Templates form (add/edit).
     *
     * @return void
     */
    public function templatesPost()
    {
        $templateID = (int)filter_input(INPUT_POST, 'templateID');
        $key = strtoupper(trim((string)filter_input(INPUT_POST, 'tplKey')));
        $label = trim((string)filter_input(INPUT_POST, 'tplLabel'));
        $type = trim((string)filter_input(INPUT_POST, 'tplType'));
        $default = filter_input(INPUT_POST, 'tplDefault') ?? '';
        $placeholder = trim((string)(filter_input(INPUT_POST, 'tplPlaceholder') ?? ''));
        $options = filter_input(INPUT_POST, 'tplOptions') ?? '';
        $required = !empty($_POST['tplRequired']) ? 1 : 0;
        $description = trim((string)(filter_input(INPUT_POST, 'tplDescription') ?? ''));
        $validation = trim((string)(filter_input(INPUT_POST, 'tplValidation') ?? ''));
        $validationMsg = trim((string)(filter_input(INPUT_POST, 'tplValidationMsg') ?? ''));
        $apiMapping = trim((string)(filter_input(INPUT_POST, 'tplApiMapping') ?? ''));
        $group = trim((string)(filter_input(INPUT_POST, 'tplGroup') ?? ''));
        $order = (int)filter_input(INPUT_POST, 'tplOrder');
        try {
            FOGCore::checkAuthAndCSRF();
            if ($key === '') {
                throw new VenueException(_('Key is required'));
            }
            if (!preg_match(self::CONFIG_KEY_REGEX, $key)) {
                throw new VenueException(
                    _('Key must be uppercase alphanumeric with underscores')
                );
            }
            if ($label === '') {
                throw new VenueException(_('Label is required'));
            }
            if (!VenueConfigTemplate::isValidType($type)) {
                throw new VenueException(_('Invalid field type'));
            }
            // Check uniqueness — if key exists, switch to edit mode
            $existingIDs = self::getSubObjectIDs(
                'VenueConfigTemplate',
                array('key' => $key)
            );
            if (!empty($existingIDs)) {
                $existingID = (int)current($existingIDs);
                if ($templateID && $existingID != $templateID) {
                    throw new VenueException(
                        _('A template with this key already exists')
                    );
                }
                // Auto-adopt the existing template for update
                $templateID = $existingID;
            }
            if ($templateID) {
                $Template = self::getClass('VenueConfigTemplate', $templateID);
                if (!$Template->isValid()) {
                    throw new VenueException(_('Template not found'));
                }
            } else {
                $Template = self::getClass('VenueConfigTemplate');
            }
            $Template
                ->set('key', $key)
                ->set('label', $label)
                ->set('type', $type)
                ->set('default', $default)
                ->set('placeholder', $placeholder)
                ->set('options', $options)
                ->set('required', $required)
                ->set('description', $description)
                ->set('validation', $validation)
                ->set('validationMsg', $validationMsg)
                ->set('apiMapping', $apiMapping)
                ->set('group', $group)
                ->set('order', $order);
            if (!$Template->save()) {
                throw new VenueException(_('Failed to save template'));
            }
            $msg = json_encode(array(
                'msg' => $templateID
                    ? _('Template updated')
                    : _('Template added'),
                'title' => _('Success')
            ));
        } catch (Exception $e) {
            $msg = json_encode(array(
                'error' => $e->getMessage(),
                'title' => _('Template Error')
            ));
        }
        echo $msg;
        exit;
    }
    /**
     * Re-seed the canonical default templates idempotently.
     *
     * POST params:
     *  - overwrite: '1' to update existing rows to canonical values;
     *               otherwise only missing keys are inserted.
     *
     * @return void
     */
    public function reseedtemplates()
    {
        header('Content-Type: application/json');
        try {
            FOGCore::checkAuthAndCSRF();
            $overwrite = (string)filter_input(INPUT_POST, 'overwrite') === '1';
            $manager = new VenueConfigTemplateManager();
            $stats = $manager->reseedDefaults($overwrite);
            echo json_encode(array(
                'title' => _('Templates reseeded'),
                'msg' => sprintf(
                    _('Inserted %d, updated %d, skipped %d'),
                    (int)$stats['inserted'],
                    (int)$stats['updated'],
                    (int)$stats['skipped']
                ),
                'stats' => $stats,
            ));
        } catch (Exception $e) {
            $this->_ajaxJsonError($e, _('Reseed Fail'));
        }
        exit;
    }
    /**
     * Delete a config template.
     *
     * @return void
     */
    public function deletetemplate()
    {
        $templateID = (int)filter_input(INPUT_GET, 'templateid');
        try {
            FOGCore::checkAuthAndCSRF();
            if (!$templateID) {
                throw new VenueException(_('No template specified'));
            }
            $Template = self::getClass('VenueConfigTemplate', $templateID);
            if (!$Template->isValid()) {
                throw new VenueException(_('Template not found'));
            }
            if (!$Template->destroy()) {
                throw new VenueException(_('Failed to delete template'));
            }
            echo json_encode(array(
                'msg' => _('Template deleted'),
                'title' => _('Success')
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'error' => $e->getMessage(),
                'title' => _('Delete Fail')
            ));
        }
        exit;
    }
    /**
     * Persist new config template order after drag-and-drop.
     *
     * @return void
     */
    public function reordertemplates()
    {
        header('Content-Type: application/json');
        try {
            FOGCore::checkAuthAndCSRF();
            $ids = filter_input(INPUT_POST, 'order');
            if (!$ids) {
                throw new VenueException(_('No order data'));
            }
            $ids = array_filter(array_map('intval', explode(',', $ids)));
            if (empty($ids)) {
                throw new VenueException(_('Invalid order data'));
            }
            // Accept optional group mapping: JSON object {id: groupName}
            $groupsJson = filter_input(INPUT_POST, 'groups');
            $groupMap = $groupsJson ? json_decode($groupsJson, true) : array();
            foreach ($ids as $position => $templateID) {
                $Template = self::getClass('VenueConfigTemplate', $templateID);
                if ($Template->isValid()) {
                    $Template->set('order', $position);
                    if (is_array($groupMap) && array_key_exists((string)$templateID, $groupMap)) {
                        $Template->set('group', $groupMap[(string)$templateID]);
                    }
                    $Template->save();
                }
            }
            echo json_encode(array(
                'msg' => _('Order saved'),
                'title' => _('Success')
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'error' => $e->getMessage(),
                'title' => _('Error')
            ));
        }
        exit;
    }
    /**
     * Re-associate selected venues — add hosts/nodes on subnets to groups.
     *
     * @return void
     */
    public function reassociatemulti()
    {
        $venueIDs = filter_input(INPUT_POST, 'venueIDArray');
        $ids = array_filter(array_map('intval', explode(',', $venueIDs)));
        $hostsAdded = 0;
        $nodesMoved = 0;
        try {
            FOGCore::checkAuthAndCSRF();
            if (empty($ids)) {
                throw new VenueException(_('No venues selected'));
            }
            Route::listem('host');
            $allHosts = json_decode(Route::getData());
            Route::listem('storagenode');
            $allNodes = json_decode(Route::getData());
            foreach ($ids as $vid) {
                $Venue = self::getClass('Venue', $vid);
                if (!$Venue->isValid()) {
                    continue;
                }
                $subnet = $Venue->get('subnet');
                $hostgroupID = $Venue->get('hostgroupID');
                $storagegroupID = $Venue->get('storagegroupID');
                // Re-associate hosts
                if ($hostgroupID) {
                    $HG = self::getClass('Group', $hostgroupID);
                    if ($HG->isValid()) {
                        $currentMembers = (array)$HG->get('hosts');
                        $hostsToAdd = array();
                        foreach ((array)($allHosts->hosts ?? array()) as $host) {
                            $ip = $host->ip ?? '';
                            if (self::_ipInSubnet($ip, $subnet)
                                && !in_array($host->id, $currentMembers)
                            ) {
                                $hostsToAdd[] = $host->id;
                            }
                        }
                        if (!empty($hostsToAdd)) {
                            $allMembers = array_merge($currentMembers, $hostsToAdd);
                            $HG->addHost($allMembers);
                            $HG->save();
                            $hostsAdded += count($hostsToAdd);
                        }
                    }
                }
                // Re-associate storage nodes
                if ($storagegroupID) {
                    foreach ((array)($allNodes->storagenodes ?? array()) as $sn) {
                        $ip = $sn->ip ?? '';
                        if (self::_ipInSubnet($ip, $subnet)
                            && $sn->storagegroupID != $storagegroupID
                        ) {
                            $Node = self::getClass('StorageNode', $sn->id);
                            if ($Node->isValid()) {
                                $Node->set('storagegroupID', $storagegroupID);
                                if ($Node->save()) {
                                    $nodesMoved++;
                                }
                            }
                        }
                    }
                }
            }
            if ($hostsAdded === 0 && $nodesMoved === 0) {
                throw new VenueException(_('Nothing to re-associate. All items are already in the correct groups.'));
            }
            $parts = array();
            if ($hostsAdded > 0) {
                $parts[] = sprintf(_('%d host(s) added'), $hostsAdded);
            }
            if ($nodesMoved > 0) {
                $parts[] = sprintf(_('%d node(s) moved'), $nodesMoved);
            }
            echo json_encode(
                array(
                    'msg' => implode(', ', $parts),
                    'title' => _('Re-associate Success')
                )
            );
        } catch (Exception $e) {
            echo json_encode(
                array(
                    'error' => $e->getMessage(),
                    'title' => _('Re-associate Fail')
                )
            );
        }
        exit;
    }

    /**
     * AJAX endpoint: proxy venue data from F1 Arcade API.
     */
    public function venuelookup()
    {
        header('Content-Type: application/json');
        try {
            $region = (string)filter_input(INPUT_GET, 'region');
            if (!VenueApiClient::isValidRegion($region)) {
                throw new VenueException(_('Invalid region'));
            }
            $venues = VenueApiClient::fetchVenues($region);
            // Apply the dev-region allowlist via the client
            $venues = array_values(
                array_filter(
                    $venues,
                    function ($v) use ($region) {
                        return is_array($v)
                            && isset($v['id'])
                            && VenueApiClient::isVenueVisible($region, $v['id']);
                    }
                )
            );
            echo json_encode(array('venues' => $venues));
            exit;
        } catch (Exception $e) {
            $this->_ajaxJsonError($e, _('Venue Lookup Failed'));
        }
    }

    /**
     * AJAX endpoint: preview or apply API config sync.
     *
     * GET  ?sub=syncconfig&id=N        → returns diff of current vs API values
     * POST ?sub=syncconfig&id=N  keys  → applies selected overrides
     */
    public function syncconfig()
    {
        header('Content-Type: application/json');
        try {
            $venueID = (int)filter_input(INPUT_GET, 'id');
            if (!$venueID) {
                throw new VenueException(_('Missing venue ID'));
            }
            $Venue = new Venue($venueID);
            if (!$Venue->isValid()) {
                throw new VenueException(_('Venue not found'));
            }
            $region = $Venue->get('apiRegion');
            $venueRef = $Venue->get('apiVenueRef');
            if (!$region || !$venueRef) {
                throw new VenueException(_('Venue is not associated with an API venue'));
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                FOGCore::checkAuthAndCSRF();
                $this->_syncconfigApply($Venue);
            } else {
                $this->_syncconfigPreview($Venue, $region, $venueRef);
            }
            exit;
        } catch (Exception $e) {
            $this->_ajaxJsonError($e, _('Sync Failed'));
        }
    }

    /**
     * Preview: fetch fresh API data, compare with current config, return diff.
     */
    private function _syncconfigPreview($Venue, $region, $venueRef)
    {
        $venueID = $Venue->get('id');
        // Re-fetch fresh data from API (throws VenueException on failure)
        $freshData = $this->_fetchApiVenueDataJson($region, $venueRef);
        // Save the refreshed raw data
        $Venue->set('apiVenueData', $freshData);
        $Venue->save();

        $apiData = json_decode($freshData, true);
        if (!is_array($apiData)) {
            throw new VenueException(_('Invalid API response format'));
        }

        // Load templates with apiMapping
        $templates = self::getClass('VenueConfigTemplateManager')
            ->find(array(), 'AND', 'vctOrder', 'ASC');

        // Load current overrides
        $configs = self::getClass('VenueConfigManager')
            ->find(array('venueID' => $venueID));
        $overrides = array();
        foreach ((array)$configs as $config) {
            $overrides[$config->get('key')] = $config->get('value');
        }

        $changes = array();
        foreach ((array)$templates as $tpl) {
            $mapping = $tpl->get('apiMapping');
            if (!$mapping) {
                continue;
            }
            $apiValue = VenueConfigMappingResolver::resolve($apiData, $mapping);
            if ($apiValue === null) {
                continue;
            }
            $apiValue = (string)$apiValue;
            $key = $tpl->get('key');
            // If no override is set, the form already displays the API-mapped
            // value as its effective fallback — syncing would just create a
            // redundant override row. Skip those fields entirely.
            if (!array_key_exists($key, $overrides)) {
                continue;
            }
            $currentValue = $overrides[$key];
            // Decrypt password values for comparison
            if ($tpl->get('type') === 'password' && $currentValue !== null) {
                $currentValue = VenueConfigCrypto::decrypt($currentValue);
            }
            $currentValue = (string)$currentValue;
            // Only show if the explicit override differs from the API value
            if ($currentValue !== $apiValue) {
                $changes[] = array(
                    'key' => $key,
                    'label' => $tpl->get('label'),
                    'current' => $currentValue !== '' ? $currentValue : '(empty)',
                    'api' => $apiValue,
                    'type' => $tpl->get('type'),
                );
            }
        }

        echo json_encode(
            array(
                'changes' => $changes,
                'apiHash' => hash('sha256', $freshData),
            )
        );
    }

    /**
     * Apply: save selected keys with their API-mapped values.
     */
    private function _syncconfigApply($Venue)
    {
        $venueID = $Venue->get('id');
        $keysRaw = filter_input(INPUT_POST, 'keys');
        $keys = json_decode((string)$keysRaw, true);
        if (!is_array($keys) || empty($keys)) {
            throw new VenueException(_('No fields selected'));
        }
        // Guard against TOCTOU between preview and apply
        $postedHash = filter_input(INPUT_POST, 'apiHash');
        $currentHash = hash('sha256', (string)($Venue->get('apiVenueData') ?: ''));
        if (!$postedHash || !hash_equals($currentHash, (string)$postedHash)) {
            throw new VenueException(
                _('API data has changed since preview. Please refresh.')
            );
        }

        $apiData = json_decode($Venue->get('apiVenueData') ?: '{}', true);
        if (!is_array($apiData)) {
            throw new VenueException(_('Invalid API response format'));
        }

        // Load templates keyed by key name
        $templates = self::getClass('VenueConfigTemplateManager')
            ->find(array(), 'AND', 'vctOrder', 'ASC');
        $tplByKey = array();
        foreach ((array)$templates as $tpl) {
            $tplByKey[$tpl->get('key')] = $tpl;
        }

        $updated = 0;
        foreach ($keys as $key) {
            if (!isset($tplByKey[$key])) {
                continue;
            }
            $tpl = $tplByKey[$key];
            $mapping = $tpl->get('apiMapping');
            if (!$mapping) {
                continue;
            }
            $apiValue = VenueConfigMappingResolver::resolve($apiData, $mapping);
            if ($apiValue === null) {
                continue;
            }
            $apiValue = (string)$apiValue;
            // Encrypt if password type
            if ($tpl->get('type') === 'password') {
                $apiValue = VenueConfigCrypto::encrypt($apiValue);
            }
            // Upsert the config override
            $existing = self::getClass('VenueConfigManager')
                ->find(array('venueID' => $venueID, 'key' => $key));
            if (!empty($existing)) {
                $configObj = array_shift($existing);
                $configObj->set('value', $apiValue);
                $configObj->save();
            } else {
                self::getClass('VenueConfig')
                    ->set('venueID', $venueID)
                    ->set('key', $key)
                    ->set('value', $apiValue)
                    ->save();
            }
            $updated++;
        }

        echo json_encode(array(
            'msg' => sprintf(_('%d field(s) updated from API'), $updated),
            'title' => _('Sync Complete')
        ));
    }
}
