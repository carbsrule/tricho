<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package meta_xml
 */

/**
 * Stores meta-data about a column that will store image data
 * @package meta_xml
 */
class ImageColumn extends FileColumn {
    private $variants = array();
    
    /**
     * Creates a DOMElement that represents this column (for use in tables.xml)
     * @param DOMDocument $doc The document to which this node will belong
     * @return DOMElement
     * @author benno, 2012-10-27
     */
    function toXMLNode (DOMDocument $doc) {
        $node = parent::toXMLNode ($doc);
        foreach ($this->variants as $name => $data) {
            $param = HtmlDom::appendNewChild ($node, 'param');
            $param->setAttribute('name', 'variant');
            $param->setAttribute('value', $name);
            foreach ($data as $name => $val) {
                $paramdata = HtmlDom::appendNewChild ($param, 'paramdata');
                $paramdata->setAttribute('name', $name);
                $paramdata->setAttribute('value', $val);
            }
        }
        return $node;
    }
    
    
    /**
     * @author benno 2012-10-27
     */
    function applyXMLNode (DOMElement $node) {
        parent::applyXMLNode ($node);
        $param_nodes = $node->getElementsByTagName ('param');
        $variants = array ();
        foreach ($param_nodes as $param) {
            $name = $param->getAttribute ('name');
            if ($name != 'variant') continue;
            $value = $param->getAttribute ('value');
            $data_nodes = $param->getElementsByTagName('paramdata');
            if ($data_nodes->length == 0) {
                throw new InvalidColumnConfigException ("Image variant missing paramdata");
            }
            $variants[$value] = array();
            foreach ($data_nodes as $data) {
                $data_name = $data->getAttribute('name');
                $data_value = $data->getAttribute('value');
                $variants[$value][$data_name] = $data_value;
            }
        }
        foreach ($variants as $name => $data) {
            $this->addVariant($name, $data);
        }
    }
    
    
    /**
     * Adds capacity for a sized variant of each image
     * @author benno 2012-10-27
     */
    function addVariant($name, array $data) {
        $name = (string) $name;
        if ($data['process'] == 'exact') unset($data['min_size']);
        foreach ($data as $key => $val) {
            switch ($key) {
            case 'process':
                if (!in_array($val, array('resize', 'exact', 'crop'))) {
                    throw new Exception ("Unknown processing method");
                }
                break;
            
            case 'max_size':
                if (!preg_match('/^[0-9]+x[0-9]+$/', $val)) {
                    throw new Exception ("Invalid max size");
                }
                break;
            
            case 'min_size':
                if (!preg_match('/^(max|width|height|either|[0-9]+x[0-9]+)$/', $val)) {
                    throw new Exception ("Invalid min size: " . (string) $val);
                }
            }
        }
        $this->variants[$name] = $data;
    }
    
    
    /**
     * Gets the (key of the) smallest variant
     * @author benno 2012-12-05
     */
    function getSmallestVariant() {
            $smallest_value = array(3, 1000000000);
            $smallest_key = '';
            foreach ($this->variants as $key => $variant) {
                    list($w, $h) = explode('x', $variant['max_size']);
                    $w = (int) $w;
                    $h = (int) $h;
                    if ($w == 0 and $h == 0) {
                            $cat = 2;
                    } else if ($w == 0 or $h == 0) {
                            $cat = 1;
                    } else {
                            $cat = 0;
                    }
                    $value = array($cat, (float) $w * (float) $h);
                    if ($value < $smallest_value) {
                            $smallest_value = $value;
                            $smallest_key = $key;
                    }
            }
            return $smallest_key;
    }
    
    
    function getConfigArray () {
        $config = parent::getConfigArray ();
        $config['storeloc'] = $this->storage_location;
        if ($this->max_file_size > 0) {
            $config['max_file_size'] = $this->max_file_size;
        }
        $config['variants'] = $this->variants;
        return $config;
    }
    
    
    static function renderVariantForm($id, $name, $variant) {
        $id = (int) $id;
        $num = $id + 1;
        $vid = 'variants[' . ((int) $id) . ']';
        $render = "<p class=\"fake-tr\">\n";
        $render .= "<span class=\"fake-td left-col\">" .
            "<span style=\"color: #BBB;\">{$num}</span> Name</span>";
        $render .= "<span class=\"fake-td right-col\">" .
        "<input type=\"text\" name=\"image_{$vid}[name]\" value=\"" . hsc($name) .
            "\"></span></p>\n";
        
        @list($width, $height) = explode('x', $variant['max_size']);
        $width = (int) $width;
        $height = (int) $height;
        if ($width == 0) $width = '';
        if ($height == 0) $height = '';
        $render .= "<p class=\"fake-tr\">\n";
        $render .= "<span class=\"fake-td left-col\">Max size</span>";
        $render .= "<span class=\"fake-td right-col\">" .
            "<input type=\"text\" name=\"image_{$vid}[max_width]\" value=\"" . hsc($width) .
            "\" size=\"3\"> &times; <input type=\"text\" name=\"image_{$vid}[max_height]\" value=\"" . hsc($height) .
            "\" size=\"3\"></span></p>\n";
        
        $handling_options = array(
            'resize' => 'Proportional resize',
            'exact' => 'Exact size',
            'crop' => 'Resize and crop',
        );
        if ($id != 0) unset($handling_options['exact']);
        $render .= "<p class=\"fake-tr\">\n";
        $render .= "<span class=\"fake-td left-col\">Handling</span>";
        $render .= "<span class=\"fake-td right-col\">" .
            "<select name=\"image_{$vid}[handling]\"" .
            " onchange=\"image_exact_no_minimum(this, {$num});\">\n";
        foreach ($handling_options as $key => $val) {
            $render .= '<option value="' . hsc($key) . '"';
            if ($key == @$variant['process']) $render .= ' selected="selected"';
            $render .= '>' . hsc($val) . "</option>\n";
        }
        $render .= "</select></span></p>\n";
        
        if ($num == 1) {
            $class = 'fake-tr';
            if (@$variant['process'] == 'exact') {
                $class .= ' display-none';
            }
            $render .= "<p class=\"{$class}\" id=\"image_min_size{$num}\">\n";
            $render .= "<span class=\"fake-td left-col\">Minimum size</span>";
            $render .= "<span class=\"fake-td right-col\">" .
                "<label for=\"min_as_max{$num}\">" .
                "<input type=\"radio\" id=\"min_as_max{$num}\"" .
                " name=\"image_{$vid}[min_size]\" value=\"max\"";
            if (@$variant['min_size'] == 'max') $render .= ' checked="checked"';
            $render .= ">Same as max size</label><br>\n";
            
            $checked = array();
            if (@$variant['min_size'] == 'either') {
                $checked['width'] = true;
                $checked['height'] = true;
            } else if (@$variant['min_size'] == 'width') {
                $checked['width'] = true;
            } else if (@$variant['min_size'] == 'height') {
                $checked['height'] = true;
            }
            $render .= "<label for=\"min_match{$num}\">" .
                "<input type=\"radio\" id=\"min_match{$num}\"" .
                " name=\"image_{$vid}[min_size]\" value=\"match\"";
            if (count($checked)) $render .= ' checked="checked"';
            $render .= ">Match</label>\n";
            $render .= "<label for=\"min_match_w{$num}\">" .
                "<input type=\"checkbox\" id=\"min_match_w{$num}\"" .
                " name=\"image_{$vid}[min_size_dim][]\" value=\"w\"";
            if (@$checked['width']) $render .= ' checked="checked"';
            $render .= " onclick=\"document.getElementById('min_match{$num}').checked = true;\">" .
                "width</label>\n";
            $render .= "<label for=\"min_match_h{$num}\">" .
                "<input type=\"checkbox\" id=\"min_match_h{$num}\"" .
                " name=\"image_{$vid}[min_size_dim][]\" value=\"h\"";
            if (@$checked['height']) $render .= ' checked="checked"';
            $render .= " onclick=\"document.getElementById('min_match{$num}').checked = true;\">" .
                "height</label><br>\n";
            
            if (preg_match('/^[0-9]+x[0-9]+$/', @$variant['min_size'])) {
                $checked = ' checked="checked"';
                @list($width, $height) = explode('x', $variant['min_size']);
                $width = (int) $width;
                $height = (int) $height;
                if ($width == 0) $width = '';
                if ($height == 0) $height = '';
            } else {
                $checked = '';
                $width = $height = '';
            }
            $render .= "<label for=\"min_dim{$num}\">" .
                "<input type=\"radio\" id=\"min_dim{$num}\"";
            $render .= " name=\"image_{$vid}[min_size]\" value=\"dim\"{$checked}>" .
                "Specific dimensions</label>\n";
            $render .= "<input type=\"text\" name=\"image_{$vid}[min_size_dim_w]\"" .
                " size=\"3\" value=\"{$width}\"" .
                " onchange=\"document.getElementById('min_dim{$num}').checked = true;\">";
            $render .= " &times; <input type=\"text\" name=\"image_{$vid}[min_size_dim_h]\"" .
                " size=\"3\" value=\"{$height}\"" .
                " onchange=\"document.getElementById('min_dim{$num}').checked = true;\"><br>";
            $render .= "</span></p>\n";
        }
        
        return $render;
    }
    
    
    static function getConfigFormFields(array $config, $class) {
        $fields = self::getFileConfigFormFields ($config, $class);
        
        $fields .= "<p class=\"fake-tr\">\n";
        $fields .= "<span class=\"fake-td left-col\">Types allowed</span>";
        $fields .= "<span class=\"fake-td\">";
        $types = array('PNG', 'JPEG', 'GIF');
        if (count(@$config['types_allowed']) > 0) {
            $allowed_types = $config['types_allowed'];
        } else {
            $allowed_types = $types;
        }
        foreach ($types as $type) {
            $ltype = strtolower($type);
            $fields .= "<label for=\"img_allow_{$ltype}\">";
            $fields .= "<input id=\"img_allow_{$ltype}\" type=\"checkbox\" " .
                "name=\"{$class}_types_allowed[]\" value=\"" . hsc($ltype) . '"';
            if (in_array($ltype, $allowed_types)) $fields .= ' checked="checked"';
            $fields .= ">{$type}</label>";
        }
        
        $variant_id = 0;
        $config['variants'] = (array) @$config['variants'];
        foreach ($config['variants'] as $name => $variant) {
            $fields .= self::renderVariantForm($variant_id, $name, $variant);
            ++$variant_id;
        }
        $fields .= self::renderVariantForm($variant_id, '', array());
        
        return $fields;
    }
    
    
    /**
     * @author benno, 2012-11-14
     */
    function applyConfig(array $config, array &$errors) {
        parent::applyConfig($config);
        if (!is_array(@$config['image_variants'])) return;
        $this->variants = array();
        foreach ($config['image_variants'] as $data) {
            $name = @trim($data['name']);
            if ($name == '') continue;
            $min = '';
            if ($data['min_size'] == 'max') {
                $min = 'max';
            } else if ($data['min_size'] == 'match') {
                $dimensions = array();
                if (is_array($data['min_size_dim'])) {
                    if (in_array('w', $data['min_size_dim'])) $dimensions[] = 'width';
                    if (in_array('h', $data['min_size_dim'])) $dimensions[] = 'height';
                    if (count($dimensions) == 2) {
                        $min = 'either';
                    } else if (count($dimensions) > 0) {
                        $min = reset($dimensions);
                    }
                }
            } else if ($data['min_size'] == 'dim') {
                $min = ((int) $data['min_size_dim_w']) . 'x' . ((int) $data['min_size_dim_h']);
            }
            $variant = array(
                'process' => (string) $data['handling'],
                'max_size' => ((int) $data['max_width']) . 'x' . ((int) $data['max_height']),
            );
            if ($min) $variant['min_size'] = $min;
            $this->addVariant($name, $variant);
        }
    }
    
    
    /**
     * @author benno, 2012-12-05
     */
    function getInputField (Form $form, $input_value = '', $primary_key = null, $field_params = array ()) {
        $input = '<input type="hidden" name="MAX_FILE_SIZE" value="'.
            $this->getMaxFileSize(). '">';

        $input .= '<input type="file" name="'. $this->name;
        if (isset($field_params['change_event'])) {
            $out_txt .= ' onchange="'. $field_params['change_event']. '"';
        }
        $input .= '"> ';
        
        // display the current file if there is one
        if ($input_value instanceof UploadedFile) {
            $input .= 'Unsaved file: '. $input_value->getName ();
        } else if ($primary_key !== null and $input_value != '') {
            $file_path = '../'. $this->storage_location;
            if (substr ($file_path, -1) != '/') $file_path .= '/';
            
            $file_name = $this->getFullMask (). '.'. implode (',', $primary_key);
            $suffix = $this->getSmallestVariant();
            $file_name .= ($suffix? '.' . $suffix: '');
            $file_path .= $file_name;
            
            if (file_exists ($file_path)) {
                $input .= "Current file: <a href=\"../file.php?f={$file_name}\">{$input_value}</a>";
            } else {
                $input .= "<span class=\"error\">File {$input_value} ($file_path) doesn't exist</span>";
            }
        } else if ($form->getType() == 'edit') {
            $input .= "No file";
        }
        
        return $input;
    }
    
    
    /**
     * @author benno, 2012-11-26
     */
    function collateInput ($input, &$original_value) {
        $safe_name = $this->getPostSafeName ();
        $this->validateUpload($input);
        
        // TODO: use UploadFailedException
        $err = @$input['error'];
        if ($err === UPLOAD_ERR_OK) {
            $type_ok = false;
            switch($input['type']) {
            case 'image/jpeg':
            case 'image/pjpeg':
                if (in_array('jpeg', $this->types_allowed)) $type_ok = true;
                break;
            case 'image/png':
                if (in_array('png', $this->types_allowed)) $type_ok = true;
                break;
            case 'image/gif':
                if (in_array('gif', $this->types_allowed)) $type_ok = true;
                break;
            }
            if (!$type_ok) {
                throw new DataValidationException('Invalid file type: ' . $input['type']);
            }
            $file = new UploadedImage($input);
            $size = $file->getSize();
            
            $variant = reset($this->variants);
            
            // check exact size
            if ($variant['process'] == 'exact') {
                $size_match = true;
                list($width, $height) = @explode('x', $variant['max_size'], 2);
                $width = (int) $width;
                $height = (int) $height;
                if ($size['w'] != $width) $size_match = false;
                if ($size['h'] != $height) $size_match = false;
                if (!$size_match) {
                    throw new DataValidationException('Dimensions do not match');
                }
            
            // check min size
            } else {
                $size_match = true;
                list($width, $height) = @explode('x', $variant['min_size'], 2);
                $width = (int) $width;
                $height = (int) $height;
                if ($size['w'] < $width) $size_match = false;
                if ($size['h'] < $height) $size_match = false;
                if (!$size_match) {
                    throw new DataValidationException('Dimensions are too small');
                }
            }
            
            $original_value = $file;
            return array ($this->name => $file);
        } else if ($err === UPLOAD_ERR_NO_FILE) {
            if ($original_value instanceof UploadedFile) {
                return array ($this->name => $original_value);
            }
            return array ();
        }
        return array ();
    }
    
    
    /**
     * Saves an uploaded file to be attached to this column
     * @author benno, 2012-11-27
     * @param UploadedImage $file The image to save
     * @param mixed $pk The primary key of the value. Can be a string (only if
     *              the table has 1 PK column), or an array of strings
     */
    function saveData ($file, $pk) {
        if (!($file instanceof UploadedFile)) return;
        
        $path = ROOT_PATH_FILE . $this->storage_location;
        if (substr($path, - 1) != '/') $path .= '/';
        
        if (is_array($pk)) $pk = implode(',', $pk);
        $file_name_base = $path . $this->getFullMask() . '.' . $pk . '.';
        $temp_file = $file_name_base . '_temp';
        file_put_contents($temp_file, $file->getData());
        
        foreach ($this->variants as $name => $variant) {
            $file_name = $file_name_base . $name;
            $this->saveVariant($variant, $temp_file, $file_name);
        }
        unlink($temp_file);
    }
    
    
    function saveVariant($variant, $original_path, $variant_path) {
        $jpeg_quality = 90;
        if (defined('DEFAULT_JPEG_QUALITY')) $jpeg_quality = DEFAULT_JPEG_QUALITY;
        
        if ($variant['process'] == 'exact') {
            copy($original_path, $variant_path);
        } else if (in_array($variant['process'], array('resize', 'crop'))) {
            list($width, $height) = explode('x', $variant['max_size']);
            $width = (int) $width;
            $height = (int) $height;
            $crop = ($variant['process'] == 'crop');
            make_sized_image(
                $original_path,
                $variant_path,
                $width,
                $height,
                '',
                $jpeg_quality,
                $crop
            );
        } else {
            return;
        }
        apply_file_security($variant_path);
    }
}
?>
