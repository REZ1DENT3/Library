<?php

namespace Deimos;

/**
 * Class Element_Form
 * @package Deimos
 */
class Element_Form
{

    /**
     * @var null|bool
     */
    public $validate = null;

    /**
     * @var null|string
     */
    public $value = null;

    /**
     * @var null|string
     */
    public $regexp = null;

    /**
     * @var string
     */
    public $region_default;

    /**
     * @var IDN
     */
    private static $_idn = null;

    /**
     * @var IDNA
     */
    private static $_idna2003 = null;

    /**
     * @var IDNA
     */
    private static $_idna2008 = null;

    /**
     * @var array
     */
    private $_messages = array(
        'is_null' => '',
        'is_valid' => '',
        'is_not_valid' => ''
    );

    /**
     * @var array
     */
    private $_css = array(
        'is_null' => '',
        'is_valid' => '',
        'is_not_valid' => ''
    );

    /**
     * @param $name
     * @param $data
     */
    public function __construct($name, $data, $region_default = 'RU')
    {
        $this->value = $data;

        $this->region_default = $region_default;

        if (method_exists($this, 'validate_' . $name))
            $this->{'validate_' . $name}();
    }

    /**
     * @return bool
     */
    public function validate_numberic()
    {
        return $this->validate_number();
    }

    /**
     * @return bool
     */
    public function is_valid()
    {
        return $this->validate !== false;
    }

    public function get_css_class()
    {
        if (is_null($this->validate)) {
            return $this->_css['is_null'];
        }
        elseif ($this->validate) {
            return $this->_css['is_valid'];
        }
        else {
            return $this->_css['is_not_valid'];
        }
    }

    /**
     * @param string $class
     * @param null $type
     * @return bool
     */
    public function add_css_class($class = 'has-error', $type = null)
    {
        if (is_null($type)) {
            $this->_css['is_null'] = $class;
            return true;
        }
        elseif ($type === true) {
            $this->_css['is_valid'] = $class;
            return true;
        }
        elseif ($type === false) {
            $this->_css['is_not_valid'] = $class;
            return true;
        }
        return false;
    }

    /**
     * @param $msg
     * @param null $type
     * @return bool
     */
    public function add_message($msg, $type = null)
    {
        if (is_null($type)) {
            $this->_messages['is_null'] = $msg;
            return true;
        }
        elseif ($type === true) {
            $this->_messages['is_valid'] = $msg;
            return true;
        }
        elseif ($type === false) {
            $this->_messages['is_not_valid'] = $msg;
            return true;
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function get_message()
    {
        if (is_null($this->validate)) {
            return $this->_messages['is_null'];
        }
        elseif ($this->validate) {
            return $this->_messages['is_valid'];
        }
        else {
            return $this->_messages['is_not_valid'];
        }
    }

    /**
     * @param int $min
     * @param int $max
     * @return bool
     */
    public function validate_password($min = 8, $max = 32)
    {
        $bool = $this->validate_length($min, $max);

        $isset_char_lower = (bool)preg_match('/[а-яёa-z]/u', $this->value);
        $bool = $isset_char_lower && $bool;

        $isset_char_upper = (bool)preg_match('/[А-ЯЁA-Z]/u', $this->value);
        $bool = $isset_char_upper && $bool;

        $isset_char_digits = (bool)preg_match('/\d/u', $this->value);
        $bool = $isset_char_digits && $bool;

        $this->validate = $bool;
        return $this->validate;
    }

    /**
     * @param $string
     * @return string
     */
    private function ucfirst($string)
    {
        if (empty($string))
            return '';

        $string = mb_strtolower($string);
        $firstChar = mb_substr($string, 0, 1);
        $then = mb_substr($string, 1);
        return mb_strtoupper($firstChar) . $then;
    }

    /**
     * @param Element_Form $element_form
     * @return bool
     */
    public function validate_confirm(Element_Form $element_form)
    {
        $this->validate = $element_form->value == $this->value;
        return $this->validate;
    }

    /**
     * @return bool
     */
    public function validate_number()
    {
        $this->validate = is_numeric($this->value);
        return $this->validate;
    }

    /**
     * @param $value
     * @return bool
     */
    public function is_equal($value)
    {
        $this->validate = $this->value == $value;
        return $this->validate;
    }

    /**
     * @param $value
     * @return bool|null|string
     */
    public function check_captcha($value)
    {
        if (!$this->validate_length())
            return $this->validate;

        if (is_string($this->value)) {
            $this->validate = mb_strtolower($this->value) === mb_strtolower($value);
        }
        else {
            $this->validate = false;
        }
        return $this->validate;
    }

    /**
     * @param $bool
     * @return bool
     */
    public function add_condition($bool)
    {
        $this->validate = $this->validate && $bool;
        return $this->validate;
    }

    /**
     * @return bool
     */
    public function validate_login()
    {
        $this->regexp = '/[\wа-я\dЁ.-_]+/iu';
        return $this->validate_regexp();
    }

    /**
     * @return bool|null
     */
    public function checkbox_is_checked()
    {
        if (is_string($this->value))
            return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
        return null;
    }

    /**
     * @return bool
     */
    public function validate_checkbox()
    {
        return $this->checkbox_is_checked() !== null;
    }

    /**
     * @param int $min
     * @param null|int $max
     * @return bool
     */
    public function validate_length($min = 1, $max = null)
    {
        if ($this->value == null)
            return $this->validate = false;

        if (is_string($this->value))
            $this->value = trim($this->value);

        $this->validate = is_string($this->value) && !empty($this->value);
        if ($this->validate) {

            $length = mb_strlen($this->value);

            if (is_numeric($max))
                $this->validate = $length <= $max;

            $this->validate = $this->validate && $length >= $min;

        }

        return $this->validate;
    }

    /**
     * @return bool
     */
    public function validate_regexp()
    {
        if (!$this->validate_length())
            return $this->validate;

        if (!$this->regexp)
            return null;

        $length = mb_strlen($this->value);
        $bool = (bool)preg_match($this->regexp, $this->value, $out);
        $is_one = count($out) == 1;
        $bool = $bool && $is_one;

        if ($bool)
            $bool = $length == mb_strlen($out[0]);

        return $bool;
    }

    /**
     * @return bool
     */
    public function validate_email()
    {
        if (!$this->validate_length(3))
            return $this->validate;

        $this->value = mb_strtolower($this->value);
        $value = $this->idn_encode();
        $this->validate = (bool)filter_var($value, FILTER_VALIDATE_EMAIL);
        if (!$this->validate && preg_match('/[а-яё]+/ui', $this->value)) {
            try {
                $value = $this->idn_encode(2003);
                $this->validate = (bool)filter_var($value, FILTER_VALIDATE_EMAIL);
                if (!$this->validate) {
                    $value = $this->idn_encode(2008);
                    $this->validate = (bool)filter_var($value, FILTER_VALIDATE_EMAIL);
                }
            }
            catch (\Exception $e) {
                $this->validate = false;
            }
        }

        return $this->validate;
    }

    /**
     * @return bool
     */
    public function validate_lastname()
    {
        return $this->validate_name();
    }

    /**
     * @return bool
     */
    public function validate_name()
    {
        if (!$this->validate_length())
            return $this->validate;

        $this->value = $this->ucfirst($this->value);

        $this->validate = Library::is_name($this->value);

        return $this->validate;
    }

    /**
     * @return bool
     */
    public function validate_patronymic()
    {
        return $this->validate_name();
    }

    /**
     * @return bool
     */
    public function validate_phone()
    {
        if (!$this->validate_length())
            return $this->validate;

        $value = preg_replace('/[^\d]/', '', $this->value);
        if (empty($value)) {
            $this->validate = false;
            return $this->validate;
        }

        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        $phone = $phoneUtil->parse($value, $this->region_default);
        $get_region_code = $phoneUtil->getRegionCodeForCountryCode($phone->getCountryCode());

        $this->validate = $phoneUtil->isValidNumberForRegion($phone, $get_region_code);

        if ($this->validate) {
            $value = $phone->getNationalNumber();
            $country_code = $phone->getCountryCode();
            $is_ru = strtoupper($this->region_default) == 'RU';
            if ($is_ru && $country_code == 7)
                $country_code = 8;
            if (!$is_ru || strlen($value) == 10) {
                $this->value = $country_code . $value;
            }
            else {
                $this->value = $value;
            }
        }

        return $this->validate;
    }

    /**
     * @return bool
     */
    public function validate_date()
    {
        if (!$this->validate_length())
            return $this->validate;

        $this->validate = Library::is_date($this->value);

        return $this->validate;
    }

    /**
     * @return string
     */
    private function idn_encode($idn_version = null)
    {
        $options = array('idn_version' => $idn_version);
        if ($idn_version && !in_array($idn_version, array(2003, 2008)))
            return $this->value;
        if (extension_loaded('intl')) {
            $value = idn_to_ascii($this->value);
        }
        elseif ($idn_version == 2003) {
            if (!self::$_idna2003)
                self::$_idna2003 = new IDNA($options);
            $value = self::$_idna2003->encode($this->value);
        }
        elseif ($idn_version == 2008) {
            if (!self::$_idna2008)
                self::$_idna2008 = new IDNA($options);
            $value = self::$_idna2008->encode($this->value);
        }
        else {
            if (!self::$_idn)
                self::$_idn = new IDN();
            $value = self::$_idn->encode($this->value);
        }
        return $value;
    }

    /**
     * @return bool
     */
    public function validate_domain()
    {
        $value = parse_url($this->value);
        $this->validate = $value !== null;
        if ($this->validate) {
            $save_value = $this->value;
            if (isset($value['scheme'])) {
                if ($this->validate_url()) {
                    $this->value = mb_strtolower($value['host']);
                }
                else {
                    $this->value = $save_value;
                }
            }
            else {
                $this->value = parse_url('http://' . $value['path']);
                if ($this->validate_url()) {
                    $this->validate = $value !== null && isset($value['host']);
                    if ($this->validate) {
                        $this->value = mb_strtolower($value['host']);
                    }
                }
                else {
                    $this->value = $save_value;
                }
            }
        }
        return $this->validate;
    }

    /**
     * @return bool|mixed
     */
    public function validate_url()
    {
        if (!$this->validate_length())
            return $this->validate;

        $value = $this->idn_encode();
        $this->validate = (bool)filter_var($value, FILTER_VALIDATE_URL);
        if (!$this->validate) {
            $value = $this->idn_encode(2003);
            $this->validate = (bool)filter_var($value, FILTER_VALIDATE_URL);
            if (!$this->validate) {
                $value = $this->idn_encode(2008);
                $this->validate = (bool)filter_var($value, FILTER_VALIDATE_URL);
            }
        }

        return $this->validate;
    }

    /**
     * @param null|string $msg_error
     * @return bool
     */
    public function validate_datetime()
    {
        if (!$this->validate_length())
            return $this->validate;

        $this->validate = Library::is_datetime($this->value);

        return $this->validate;
    }

}

/**
 * Class Form
 * @package Deimos
 */
class Form
{

    /**
     * @var array
     */
    private $_data = array();

    /**
     * @var array
     */
    private $_row = array();

    /**
     * @param array $method
     */
    public function __construct($method = array(), $auto_validation = true)
    {
        $this->_data = $method;
        if ($auto_validation) {
            foreach ($this->_data as $name => $value) {
                $this->get($name);

                if (preg_match('/_confirm$/', $name)) {
                    $_name = preg_replace('/_confirm$/', '', $name);
                    if (isset($this->_row[$_name])) {
                        $element = $this->get($_name);
                        $this->get($name)->validate_confirm($element);
                    }
                }
            }
        }
    }

    /**
     * @param $name
     * @return Element_Form
     */
    private function _init_row($name)
    {
        return new Element_Form($name, $this->_data[$name]);
    }

    /**
     * @return bool
     */
    public function is_valid()
    {
        if (!count($this->_row))
            return null;
        $valid = true;
        foreach ($this->_row as $name => $row) {
            $valid = $valid && $row->is_valid();
        }
        return $valid;
    }

    /**
     * @param $name
     * @return null|Element_Form
     */
    public function get($name, $auto_init = false)
    {
        if (!isset($this->_data[$name])) {
            if ($auto_init) {
                $this->_data[$name] = null;
            }
            else {
                return null;
            }
        }

        if (!isset($this->_row[$name]))
            $this->_row[$name] = $this->_init_row($name);
        return $this->_row[$name];
    }

    /**
     * @param $name
     * @return Element_Form|null
     */
    public function __get($name)
    {
        return $this->get($name, true);
    }

}