<?php

use Gettext\GettextTranslator;
/**
 * Copyright 2016 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
class GetTextTemplateHelpers implements TemplateGlobalProvider
{
    /**
     * @return array
     */
    public static function get_template_global_variables()
    {
        return [
            '_T' => [
                'method' => '_t',
                'casting' => 'HTMLText',
            ],
        ];
    }

    private static function convertLocale($locale){
        switch ($locale){
            case "es_ES": return "es_ES.utf8";
            default:
                return $locale;
        }
    }

    public static function _t($domain, $msgid){
        $args = [];
        if(func_num_args() > 2)
        {
            $args = func_get_args();
            $args = array_slice($args, 2);
        }
        $msgid    = str_replace("\r\n", '', $msgid);
        $t        = new GettextTranslator();
        $language = self::convertLocale(i18n::get_locale());
        $path     = Director::baseFolder().'/gettext/Locale';

        $t->setLanguage($language);
        $t->loadDomain($domain, $path);
        $t->register();

        $msgstr = call_user_func_array("__", array_merge([$msgid], $args));

        return $msgstr;
    }
}