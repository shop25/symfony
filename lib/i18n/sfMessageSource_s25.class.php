<?php

/**
 * Class sfMessageSource_s25
 *
 * Источник для работы с упрощенной схемой хранения.
 * Поддерживает только один каталог messages.
 * Методы для изменения каталога заглушены.
 *
 * Схема:
 *
 * I18nMessage:
 *  actAs:
 *    I18n:
 *      appLevelDelete: true
 *      fields: [target]
 *  columns:
 *    source:     { type: string, notnull: true }
 *    target:     { type: string, notnull: true }
 */
class sfMessageSource_s25 extends sfMessageSource_Database
{
    /**
     * The datasource string, full DSN to the database.
     * @var string
     */
    protected $source;

    /**
     * Constructor.
     * Creates a new message source using MySQL.
     *
     * @param string $source MySQL datasource, in PEAR's DB DSN format.
     *
     * @see MessageSource::factory();
     */
    function __construct($source)
    {
        $this->source = (string)$source;
    }

    /**
     * Destructor, closes the database connection.
     */
    function __destruct()
    {
    }

    /**
     * Connects to the MySQL datasource
     *
     * @return resource MySQL connection.
     * @throws sfException, connection and database errors.
     */
    protected function connect()
    {
    }

    /**
     * Gets an array of messages for a particular catalogue and cultural variant.
     *
     * @param string $variant the catalogue name + variant
     *
     * @return array translation messages.
     */
    public function &loadData($variant)
    {
        $variant = mb_substr($variant, -2);

        $statement =
            "SELECT t.id, s.source, t.target
        FROM i18n_message_translation t
        LEFT JOIN i18n_message s ON t.id = s.id
        WHERE t.lang = '{$variant}'
        ORDER BY id ASC";

        $rows = $this->select($statement);

        $result = array();
        foreach ($rows as $row)
        {
            $source            = $row['source'];
            $result[$source][] = $row['target']; //target
            $result[$source][] = $row['id']; //id
            $result[$source][] = ''; //comments
        }

        return $result;
    }


    /**
     * Checks if a particular catalogue+variant exists in the database.
     *
     * @param string $variant catalogue+variant
     *
     * @return boolean true if the catalogue+variant is in the database, false otherwise.
     */
    public function isValidSource($variant)
    {
        $variant = mb_substr($variant, -2);

        $row =  $this->select(
            "select exists(select * FROM i18n_message_translation where lang = '{$variant}') as exist"
        );

        $result = $row[0]['exist'] === '1';

        return $result;
    }

    public function select($stmt)
    {
        return Doctrine_Manager::getInstance()
            ->getCurrentConnection()
            ->fetchAssoc($stmt);
    }
    /**
     * Заглушка
     *
     * @param string $catalogue the catalogue to add to
     *
     * @return boolean true if saved successfuly, false otherwise.
     */
    function save($catalogue = 'messages')
    {
        return 0;
    }

    /**
     * Заглушка
     *
     * @param string $message   the source message to delete.
     * @param string $catalogue the catalogue to delete from.
     *
     * @return boolean true if deleted, false otherwise.
     */
    function delete($message, $catalogue = 'messages')
    {
        return 0;
    }

    /**
     * Заглушка
     *
     * @param string $text      the source string.
     * @param string $target    the new translation string.
     * @param string $comments  comments
     * @param string $catalogue the catalogue of the translation.
     *
     * @return boolean true if translation was updated, false otherwise.
     */
    function update($text, $target, $comments, $catalogue = 'messages')
    {
        return 0;
    }

    /**
     * Returns a list of catalogue as key and all it variants as value.
     *
     * @return array list of catalogues
     */
    function catalogues()
    {
        $rows = $this->select(
            "SELECT lang FROM i18n_message_translation 
            WHERE lang is not null GROUP BY lang"
        );
        foreach ($rows as $row)
        {
            $result[] = $row[0];
        }

        return $result;
    }
}
