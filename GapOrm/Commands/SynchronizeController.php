<?php
namespace GapOrm\Commands;

use GapOrm\Drivers\PdoDriver;
use GapOrm\Exceptions\TypeNotExistException;
use GapOrm\Mapper\BaseModel;
use Safan\CliManager\CliManager;
use Safan\Safan;

class SynchronizeController
{
    /**
     * Synchronize all table fields
     *
     * @Route ('GapOrm:synchronize')
     */
    public function indexAction(){
        $models = $this->getAllModels();

        foreach($models as $model)
            $this->checkTable(new $model['namespace']);
    }

    /**
     * Check Table fields
     *
     * @param $modelObj
     * @return bool
     */
    private function checkTable($modelObj){
        if(!method_exists($modelObj, 'table'))
            return false;
        // get table name
        $tableName = $modelObj->table();
        // check table, add if not exist
        $existTable = PdoDriver::getInstance()->tableExists($tableName);
        if(!$existTable){
            $this->createTable($tableName, $modelObj->getFields());
            CliManager::getMessage('Created table ' . $tableName);
        }
        // get database table data and check
        PdoDriver::getInstance()->query('SHOW COLUMNS FROM ' . $tableName);
        $dbFields = PdoDriver::getInstance()->selectAll();

        $modelFieldNames = [];
        foreach($modelObj->getFields() as $modelField)
            $modelFieldNames[] = $modelField->identifier();

        // check db fields on model fields
        $dbFieldNames = [];
        foreach($dbFields as $dbField){
            if(!in_array($dbField->Field, $modelFieldNames))
                echo " --- Old field " . CliManager::setTextColor($dbField->Field, 'yellow') . " in table " . CliManager::setTextColor($modelObj->table(), 'green') . " --- \n\r";

            $dbFieldNames[] = $dbField->Field;
        }

        // check model fields on db fields
        foreach($modelObj->getFields() as $key => $modelField){
            if(!in_array($modelField->identifier(), $dbFieldNames)){
                // Add new fields to db table
                $previousField = false;
                if(isset($modelObj->getFields()[$key-1]))
                    $previousField = $modelObj->getFields()[$key-1];

                $this->createField($tableName, $modelField, $previousField);
                echo CliManager::setTextColor(" --- Add New field " . $modelField->identifier() . " in table " . $modelObj->table(), 'green') . " --- \n\r";
            }
        }
    }

    /**
     * Return all Model Class names
     *
     * @return array
     */
    private function getAllModels(){
        $modules = Safan::handler()->getModules();

        $modelClasses = [];
        foreach ($modules as $moduleName => $modulePath) {
            $modelsPath = APP_BASE_PATH . DS . $modulePath . DS . 'Models';

            $modelFiles = [];
            if (is_dir($modelsPath))
                $modelFiles = scandir($modelsPath);

            foreach($modelFiles as $modelFile){
                if($modelFile != '.' && $modelFile != '..' && is_dir($modelsPath . DS . $modelFile)){
                    $subModelFiles = scandir($modelsPath . DS . $modelFile);

                    foreach($subModelFiles as $subModelFile){
                        if($subModelFile != '.' && $subModelFile != '..' && is_file($modelsPath . DS . $modelFile . DS . $subModelFile)){
                            $subModelName   = substr($subModelFile, 0, -4);
                            $modelClasses[] = [
                                'name'      => $subModelName,
                                'namespace' => '\\' . $moduleName . '\\Models\\' . $modelFile . '\\' . $subModelName,
                                'file'      => $modelsPath . DS . $modelFile . DS . $subModelFile
                            ];
                        }
                    }
                }
                elseif($modelFile != '.' && $modelFile != '..' && is_file($modelsPath . DS . $modelFile)){
                    $modelName      = substr($modelFile, 0, -4);
                    $modelClasses[] = [
                        'name'      => $modelName,
                        'namespace' => '\\' . $moduleName . '\\Models\\' . $modelName,
                        'file'      => $modelsPath . DS . $modelFile
                    ];
                }

            }
        }

        return $modelClasses;
    }

    /**
     * @param $tableName
     * @param $fields
     * @return bool
     * @throws TypeNotExistException
     */
    private function createTable($tableName, $fields){
        if(empty($fields))
            return false;

        $fieldString = '';
        foreach($fields as $num => $field){
            switch ($field->type()) {
                case BaseModel::FIELD_TYPE_BOOL :
                        $str = $field->identifier() . ' INT( 11 )';
                    break;
                case BaseModel::FIELD_TYPE_INT :
                        if($field->identifier() == 'id')
                            $str = 'id INT( 11 ) AUTO_INCREMENT PRIMARY KEY';
                        else
                            $str = $field->identifier() . ' INT( 11 ) NOT NULL DEFAULT 0';
                    break;
                case BaseModel::FIELD_TYPE_FLOAT :
                        $str = $field->identifier() . ' FLOAT NOT NULL';
                    break;
                case BaseModel::FIELD_TYPE_STR :
                        if($field->length() > 0)
                            $length = $field->length();
                        else
                            $length = 255;
                        $str = $field->identifier() . ' VARCHAR( '. $length .' ) NOT NULL';
                    break;
                case BaseModel::FIELD_TYPE_DATETIME :
                        $str = $field->identifier() . ' INT( 11 ) NOT NULL DEFAULT 0';
                    break;
                case BaseModel::FIELD_TYPE_STR_ARRAY :
                        $str = $field->identifier() . ' TEXT NOT NULL';
                    break;
                case BaseModel::FIELD_TYPE_INT_ARRAY :
                        $str = $field->identifier() . ' TEXT NOT NULL';
                    break;
                case BaseModel::FIELD_TYPE_OBJ :
                        $str = $field->identifier() . ' TEXT NOT NULL';
                    break;
                default :
                    throw new TypeNotExistException();
            }

            // add separator
            if(($num+1) < sizeof($fields))
                $str .= ', ';

            $fieldString .= $str;
        }

        return PdoDriver::getInstance()->createTable($tableName, $fieldString);
    }

    /**
     * @param $tableName
     * @param $field
     * @param $previousField
     * @return mixed
     * @throws TypeNotExistException
     */
    public function createField($tableName, $field, $previousField){
        switch ($field->type()) {
            case BaseModel::FIELD_TYPE_BOOL :
                $fieldString = $field->identifier() . ' INT( 11 )';
                break;
            case BaseModel::FIELD_TYPE_INT :
                if($field->identifier() == 'id')
                    $fieldString = ' INT( 11 ) AUTO_INCREMENT PRIMARY KEY';
                else
                    $fieldString = $field->identifier() . ' INT( 11 ) NOT NULL DEFAULT 0';
                break;
            case BaseModel::FIELD_TYPE_FLOAT :
                    $fieldString = $field->identifier() . ' FLOAT NOT NULL';
                break;
            case BaseModel::FIELD_TYPE_STR :
                    if($field->length() > 0)
                        $length = $field->length();
                    else
                        $length = 255;
                    $fieldString = $field->identifier() . ' VARCHAR( '. $length .' ) NOT NULL';
                break;
            case BaseModel::FIELD_TYPE_DATETIME :
                    $fieldString = $field->identifier() . ' INT( 11 ) NOT NULL DEFAULT 0';
                break;
            case BaseModel::FIELD_TYPE_STR_ARRAY :
                    $fieldString = $field->identifier() . ' TEXT NOT NULL';
                break;
            case BaseModel::FIELD_TYPE_INT_ARRAY :
                    $fieldString = $field->identifier() . ' TEXT NOT NULL';
                break;
            case BaseModel::FIELD_TYPE_OBJ :
                    $fieldString = $field->identifier() . ' TEXT NOT NULL';
                break;
            default :
                throw new TypeNotExistException();
        }

        if($previousField)
            $after = ' AFTER ' . $previousField->identifier();
        else
            $after = ' FIRST';

        $fieldString = $fieldString . $after;

        return PdoDriver::getInstance()->createField($tableName, $fieldString);
    }
}