<?php

namespace App\Bitfield;

abstract class BitField
{
    private $value;

    public function __construct($value = 0)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function get($n)
    {
        if (is_int($n)) {
            return 0 != ($this->value & (1 << $n));
        } else {
            return 0;
        }
    }

    public function set($n, $new = true)
    {
        $this->value = ($this->value & ~(1 << $n)) | ($new << $n);
    }

    public function clear($n)
    {
        $this->set($n, false);
    }

    public function __toString()
    {
        $refl = new \ReflectionClass(get_class($this));
        $ret = array();

        foreach ($refl->getConstants() as $key => $value) {
            $ret[$key] = true === $this->get($value) ? 1 : 0;
        }

        return print_r($ret, true);
    }
}
