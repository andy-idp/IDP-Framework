<?php

namespace Core;

class Validate {

    public static function email($email) {
        if (preg_match('#^[[:alnum:]]([-_.]?[[:alnum:]])+_?@[[:alnum:]]([-.]?[[:alnum:]])+\.[a-z]{2,4}$#', $email)) {
            return true;
        } else {
            return false;
        }
    }

    public static function postalCode($postal_code) {
        if (preg_match('#^[0-9]{5,5}$#', $postal_code)) {
            return true;
        } else {
            return false;
        }
    }

    public static function phone($phone) {
        if (preg_match('#^0[1-678]([-. ]?[0-9]{2}){4}$#', $phone)) {
            return true;
        } else {
            return false;
        }
    }

}
