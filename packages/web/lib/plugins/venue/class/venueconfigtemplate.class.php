<?php
/**
 * Venue configuration template class.
 *
 * PHP version 5
 *
 * @category VenueConfigTemplate
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * Venue configuration template class — defines a config field schema.
 *
 * @category VenueConfigTemplate
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class VenueConfigTemplate extends FOGController
{
    /**
     * The table name.
     *
     * @var string
     */
    protected $databaseTable = 'venueConfigTemplate';
    /**
     * The table fields and common names.
     *
     * @var array
     */
    protected $databaseFields = array(
        'id' => 'vctID',
        'key' => 'vctKey',
        'label' => 'vctLabel',
        'type' => 'vctType',
        'default' => 'vctDefault',
        'placeholder' => 'vctPlaceholder',
        'options' => 'vctOptions',
        'required' => 'vctRequired',
        'description' => 'vctDescription',
        'validation' => 'vctValidation',
        'validationMsg' => 'vctValidationMsg',
        'apiMapping' => 'vctApiMapping',
        'group' => 'vctGroup',
        'order' => 'vctOrder',
    );
    /**
     * The required fields.
     *
     * @var array
     */
    protected $databaseFieldsRequired = array(
        'key',
        'label',
        'type',
    );
    /**
     * Valid field types.
     *
     * @var array
     */
    protected static $validTypes = array(
        'text',
        'textarea',
        'number',
        'checkbox',
        'select',
        'password',
        'url',
        'email',
    );
    /**
     * Check if the type is valid.
     *
     * @param string $type The field type.
     *
     * @return bool
     */
    public static function isValidType($type)
    {
        return in_array($type, self::$validTypes, true);
    }
    /**
     * Get valid types.
     *
     * @return array
     */
    public static function getValidTypes()
    {
        return self::$validTypes;
    }
}
