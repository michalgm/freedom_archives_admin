<?php
/**
 * Class for sequential reading of FileMaker export XML
 */
class FMXMLReader {
  protected $reader;
  protected $fields;
  protected $fieldInfoByName = array();
  protected $options;

  static public function read($data,$options=array()) {
    $reader = new XMLReader();
    $reader->XML($data);
    return new FMXMLReader($reader,$options);
  }
  	
  static public function open($url,$options=array()) {
    $reader = new XMLReader();
    $reader->open($url);
    return new FMXMLReader($reader,$options);
  }

  /**
   * @param XMLReader $reader
   * @param array $options
   */
  function __construct($reader,$options=array()) {
    $this->reader = $reader;
    $this->options = $options;
    $this->parse_metadata();
  }
  
  /**
   * Get the field information by the field name
   * @param string $field_name
   * @return array|NULL
   *
   * Returns array with keys 'NAME', 'TYPE','MAXREPEAT', and 'EMPTYOK'
   */
  public function fieldInfo($field_name) {
    $this->fieldInfoByName[$field_name];
  }

  private function option($name,$def=NULL) {
    if (isset($this->options[$name])) {
      return $this->options[$name];
    } else {
      return $def;
    }
  }

  private function array_field_value($fieldInfo,$values_array) {
    $option = $this->option('array_value');
    if ($option == 'JOIN') {
      return implode("\n",$values_array);
    }

    if ($option == 'ARRAY') {
      return $values_array;   
    }
    
    if ($option == 'FIRST'  or $fieldInfo['MAXREPEAT']!=1) {
      return $values_array[0];
    }

    return $values_array;
  }

  private function parse_metadata() {

    $fields = array();
    $reader = $this->reader;
    while($reader->read()) {
      if ($reader->nodeType==XMLReader::ELEMENT) {
        if ($reader->name=='FIELD') {
          $fieldInfo = array();
          $flag = $reader->moveToFirstAttribute();
 
          while ($flag) {
             $fieldInfo[$reader->name] = $reader->value;
             $flag = $reader->moveToNextAttribute();
          }
          $fields[] = $fieldInfo;
          $this->fieldInfoByName[$fieldInfo['NAME']] = $fieldInfo;
        }
      }
    
      if ($reader->nodeType==XMLReader::END_ELEMENT) {
        if ($reader->name=='METADATA') {
           break;
        } 
      }
    
    }
    $this->fields = $fields;
    
  }
  
  

  private function nextColumn() {
    $reader = $this->reader;
    while ($reader->read()) {
      if ($reader->nodeType==XMLReader::END_ELEMENT && $reader->name=='ROW') {
        break;
      }
      if ($reader->nodeType==XMLReader::ELEMENT && $reader->name=='COL') {
        $data = array();
        while ($reader->read()) {
          if ($reader->nodeType==XMLReader::END_ELEMENT && $reader->name=='COL') {
            break;
          }
          if ($reader->nodeType==XMLReader::ELEMENT && $reader->name=='DATA') {
            $datum = '';
            if (!$reader->isEmptyElement) {
              while ($reader->read()) {
                if ($reader->nodeType==XMLReader::END_ELEMENT &&
                               $reader->name=='DATA') {
                  break;
                }
                if ($reader->nodeType==XMLReader::TEXT) {
                   $datum .= $reader->value;
                }
              }
            }
            $data[] = $datum;
          }
        }
        return $data;
      }
    }
    return NULL;
  }

  /**
   * Read the next row of data, and return as an array (or NULL if done)
   * @return array|NULL
   */
  public function nextRow() {
    $reader = $this->reader;
    while($reader->read()) {
      if ($reader->nodeType==XMLReader::ELEMENT && $reader->name=='ROW') {
    
        $columns=array();
        while ($column=$this->nextColumn()) {
          $columns[] = $column;
        }

        $row_hash = array();
        foreach ($this->fields as $index => $fieldInfo) {
          if (isset($columns[$index])) {
            $data = $this->array_field_value($fieldInfo,$columns[$index]);
            $row_hash[$fieldInfo['NAME']] = $data;
          } else {
            // print "Missing index: $index ($fieldInfo[NAME]) ".$columns[2][0]."\n";
          }
        }
        return $row_hash;
      }
    }
    return NULL;
  }

}
