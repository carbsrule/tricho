<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use Tricho\Runtime;
use Tricho\DataUi\Form;
use Tricho\Util\HtmlDom;


/**
 * Handles Google ReCAPTCHA v3 tests for human operators
 */
class RecaptchaV3Column extends VirtualColumn
{
    protected $passedValidation = false;

    function attachInputField(Form $form, $input_value = '', $primary_key = null, $field_params = [])
    {
        $p = self::initInput($form);

        $prev = $p->previousSibling;
        if ($prev instanceof \DOMElement) {
            $prev_class = $prev->getAttribute('class');
            if (strpos($prev->getAttribute('class'), 'error') === false) {
                $prev->setAttribute('class', $prev_class . ' no-display');
            }
        }

        try {
            $recaptcha_key = Runtime::get('recaptchav3_site_key');
        } catch (\InvalidArgumentException $ex) {
            HtmlDom::appendNewText($p, 'ReCAPTCHA incorrectly configured');
            return;
        }

        $url = 'https://www.google.com/recaptcha/api.js?render=';
        $url .= $recaptcha_key;

        $css_classes = $p->getAttribute('class');
        if ($css_classes) {
            $css_classes .= ' no-display';
        } else {
            $css_classes = 'no-display';
        }
        $p->setAttribute('class', $css_classes);

        $fieldId = $form->getFieldId();
        $input = HtmlDom::appendNewChild($p, 'input', [
            'type' => 'hidden',
            'name' => $this->name,
            'id' => $fieldId,
        ]);

        $remote_script = $remote_script = HtmlDom::appendNewChild(
            $p->parentNode, 'script', ['src' => $url]
        );
        HtmlDom::appendNewText($remote_script, '');

        $local_script = HtmlDom::appendNewChild($p->parentNode, 'script');
        HtmlDom::appendNewText($local_script, "grecaptcha.ready(function() {
    grecaptcha.execute('{$recaptcha_key}', {action: 'login'}).then(function(token) {
         document.getElementById('{$fieldId}').value = token;
    });
});");

    }

    function collateInput($input, &$original_value)
    {
        try {
            $recaptcha_key = Runtime::get('recaptchav3_secret_key');
        } catch (\InvalidArgumentException $ex) {
            throw new \DataValidationException('ReCAPTCHA incorrectly configured');
        }
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $params = [
            'secret' => $recaptcha_key,
            'response' => $input,
            'remoteip' => $_SERVER['REMOTE_ADDR'],
        ];

        $req = curl_init($url);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($req, CURLOPT_POST, true);
        curl_setopt($req, CURLOPT_POSTFIELDS, http_build_query($params));
        $out = curl_exec($req);
        $http_code = curl_getinfo($req, CURLINFO_HTTP_CODE);
        if ($out === false or $http_code != 200) {
            $ex = new \DataValidationException('CAPTCHA failed to connect');
            curl_close($req);
            throw $ex;
        }
        curl_close($req);

        $response = json_decode($out, true);
        if (empty($response['success'])) {
            throw new \DataValidationException('Invalid CAPTCHA response');
        }

        $this->passedValidation = true;

        return [];
    }

    function isInputEmpty(array $input)
    {
        if ($this->passedValidation) {
            return false;
        }
        return true;
    }
}
