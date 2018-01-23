<?php

namespace Core;

class TwigExtension extends \Twig_Extension {

    public function getFilters() {
        return array(
            new \Twig_SimpleFilter("translate", [$this, 'translate'], ['needs_context' => true, 'is_safe' => ['html']]),
            new \Twig_SimpleFilter("truncate", [$this, 'truncate'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter("date_format", [$this, 'date_format'], ['is_safe' => ['html']]),
        );
    }

    public function getFunctions() {
        return array(
        );
    }

    public function translate($context, $value) {
        $langue_actuelle = $context['CURRENT_LANGUAGE'];

        if (!empty($langue_actuelle)) {
            if (!empty($context['TRANSLATION'][$value])) {
                $value = $context['TRANSLATION'][$value];
            }
        }

        return $value;
    }

    public function truncate($value, $length = 50) {
        $value = strip_tags($value);
        if (strlen($value) >= $length) {
            $value  = substr($value, 0, $length);
            $last   = strrpos($value, ' ');
            $value  = substr($value, 0, $last);
            $value .= ' ...';
        }
        return $value;
    }
    
    public function date_format($value, $format = "d/m/Y") {
        if (empty($value)) {
            $value = time();
        }
        return \date($format, $value);        
    }
}
