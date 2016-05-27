<?php
/**
 * This file is part of the Safan package.
 *
 * (c) Harut Grigoryan <ceo@safanlab.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace GapOrm\Mapper;

class FieldMapper
{
    /**
     * Database table
     *
     * @var
     */
    protected $table;

    /**
     * identifier of the property
     *
     * @var
     */
    protected $identifier;

    /**
     * Model::TYPE_... type of the property
     *
     * @var
     */
    protected $type;

    /**
     * Sql expression (if different from ident)
     *
     * @var
     */
    protected $sql;

    /**
     * Not use for INSERT queries
     *
     * @var bool
     */
    protected $noUpdate;

    /**
     * Not use for INSERT queries
     *
     * @var bool
     */
    protected $noInsert;

    /**
     * Field length
     *
     * @var int
     */
    protected $length;

    /**
     * Field is Primary Key
     *
     * @var bool
     */
    protected $pk;

    /**
     * @param $table
     * @param $identifier
     * @param $type
     * @param int $length for Model synchronization
     */
    public function __construct($table, $identifier, $type, $length = 0)
    {
        $this->identifier = $identifier;
        $this->type       = $type;
        $this->sql        = $identifier;
        $this->length     = $length;
        $this->table      = $table;
        $this->noInsert   = false;
        $this->noUpdate   = false;
        $this->pk         = false;
    }

    /**
     * @param null $noInsert
     * @return bool
     */
    public function noInsert($noInsert = NULL)
    {
        if (!is_null($noInsert)) {
            $this->noInsert = (bool)$noInsert;
        }

        return $this->noInsert;
    }

    /**
     * @param null $noUpdate
     * @return bool
     */
    public function noUpdate($noUpdate = NULL)
    {
        if (!is_null($noUpdate)) {
            $this->noUpdate = (bool)$noUpdate;
        }

        return $this->noUpdate;
    }

    /**
     * @param null $pk
     * @return bool
     */
    public function pk($pk = NULL)
    {
        if (!is_null($pk)) {
            $this->pk = (bool)$pk;
        }

        return $this->pk;
    }

    /**
     * @param null $sql
     * @return null
     */
    public function sql($sql = NULL)
    {
        if (!is_null($sql)) {
            $this->sql = $sql;
        }

        return $this->sql;
    }

    /**
     * @param null $identifier
     * @return null
     */
    public function identifier($identifier = NULL)
    {
        if (!is_null($identifier)) {
            $this->identifier = $identifier;
        }

        return $this->identifier;
    }

    /**
     * @return int
     */
    public function length()
    {
        return $this->length;
    }

    /**
     * @param null $type
     * @return null
     */
    public function type($type = NULL)
    {
        if (!is_null($type)) {
            $this->type = $type;
        }

        return $this->type;
    }

    /**
     * @param null $table
     * @return null
     */
    public function table($table = NULL)
    {
        if (!is_null($table)) {
            $this->table = $table;
        }

        return $this->table;
    }
}