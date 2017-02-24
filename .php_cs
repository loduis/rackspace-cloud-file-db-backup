<?php

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@PSR2' => true,
        'no_unused_imports' => true,
        'lowercase_constants' => true,
        'array_syntax' => array('syntax' => 'short'),
    ));
