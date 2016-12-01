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
   * The DSN array property, parsed by PEAR's DB DSN parser.
   * @var array
   */
  protected $dsn;

  /**
   * A resource link to the database
   * @var mysqli
   */
  protected $db;

  /**
   * Constructor.
   * Creates a new message source using MySQL.
   *
   * @param string $source  MySQL datasource, in PEAR's DB DSN format.
   * @see MessageSource::factory();
   */
  function __construct($source)
  {
    $this->source = (string) $source;
    $this->dsn = $this->parseDSN($this->source);
    $this->db = $this->connect();
  }

  /**
   * Destructor, closes the database connection.
   */
  function __destruct()
  {
    @mysqli_close($this->db);
  }

  /**
   * Connects to the MySQL datasource
   *
   * @return resource MySQL connection.
   * @throws sfException, connection and database errors.
   */
  protected function connect()
  {
    $dsninfo = $this->dsn;

    if (isset($dsninfo['protocol']) && $dsninfo['protocol'] == 'unix')
    {
      $dbhost = ':'.$dsninfo['socket'];
    }
    else
    {
      $dbhost = $dsninfo['hostspec'] ? $dsninfo['hostspec'] : 'localhost';
      if (!empty($dsninfo['port']))
      {
        $dbhost .= ':'.$dsninfo['port'];
      }
    }
    $user = $dsninfo['username'];
    $pw = $dsninfo['password'];

    $connect_function = 'mysqli_connect';

    if (!function_exists($connect_function))
    {
      throw new RuntimeException('The function mysqli_connect() does not exist. Please confirm MySQLi is enabled in php.ini');
    }

    if ($dbhost && $user && $pw)
    {
      $conn = @$connect_function($dbhost, $user, $pw);
    }
    elseif ($dbhost && $user)
    {
      $conn = @$connect_function($dbhost, $user);
    }
    elseif ($dbhost)
    {
      $conn = @$connect_function($dbhost);
    }
    else
    {
      $conn = false;
    }

    if (empty($conn))
    {
      throw new sfException(sprintf('Error in connecting to %s.', $dsninfo));
    }

    if ($dsninfo['database'])
    {
      if (!@mysqli_select_db($conn, $dsninfo['database']))
      {
        throw new sfException(sprintf('Error in connecting database, dsn: %s.', $dsninfo));
      }
    }
    else
    {
      throw new sfException('Please provide a database for message translation.');
    }

    return $conn;
  }

  /**
   * Gets the database connection.
   *
   * @return db database connection.
   */
  public function connection()
  {
    return $this->db;
  }

  /**
   * Gets an array of messages for a particular catalogue and cultural variant.
   *
   * @param string $variant the catalogue name + variant
   * @return array translation messages.
   */
  public function &loadData($variant)
  {
    $variant = mb_substr(mysqli_real_escape_string($this->db, $variant), -2);
  
    $statement =
      "SELECT t.id, s.source, t.target, '' comments
        FROM i18n_message_translation t
        LEFT JOIN i18n_message s ON t.id = s.id
        WHERE t.lang = '{$variant}'
        ORDER BY id ASC";

    $rs = mysqli_query($this->db, $statement);

    $result = array();

    if ($rs === false)
    {
      return $result;
    }

    while ($row = mysqli_fetch_array($rs, MYSQLI_NUM))
    {
      $source = $row[1];
      $result[$source][] = $row[2]; //target
      $result[$source][] = $row[0]; //id
      $result[$source][] = $row[3]; //comments
    }

    return $result;
  }


  /**
   * Checks if a particular catalogue+variant exists in the database.
   *
   * @param string $variant catalogue+variant
   * @return boolean true if the catalogue+variant is in the database, false otherwise.
   */
  public function isValidSource($variant)
  {
    $variant = mb_substr(mysqli_real_escape_string($this->db, $variant), -2);
  
    $rs = mysqli_query($this->db, "SELECT count(shit) FROM (SELECT DISTINCT '' shit FROM i18n_message_translation t WHERE t.lang = '{$variant}') d");
  
    $row = mysqli_fetch_array($rs, MYSQLI_NUM);

    $result = $row && $row[0] == '1';

    return $result;
  }

  /**
   * Заглушка
   *
   * @param string $catalogue the catalogue to add to
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
    $statement = "SELECT DISTINCT concat('messages.', lang) FROM i18n_message_translation ORDER BY lang";
    $rs = mysqli_query($this->db, $statement);
    $result = array();
    while($row = mysqli_fetch_array($rs, MYSQLI_NUM))
    {
      $details = explode('.', $row[0]);
      if (!isset($details[1]))
      {
        $details[1] = null;
      }

      $result[] = $details;
    }

    return $result;
  }
}
