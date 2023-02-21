<?php

namespace L5Swagger\Console;

use DateInterval;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\GeneratorFactory;
use Throwable;

class GenerateModelDocsCommand extends Command {
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'l5-swagger:generate_model {class}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Generate comments to exist model';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle() {
    $className = $this->argument('class');
    $class = "App\\Models\\" . $className;

    if (!class_exists($class)) {
      echo "ERROR: class $class not found" . PHP_EOL;
      return 0;
    }

    $model = new $class();

    $return = '/**' . PHP_EOL;
    $return .= ' *' . PHP_EOL;
    $return .= ' * @OA\Schema(' . PHP_EOL;

    $return .= ' * required={"';
    $return .= implode('", "', $this->getRequiredColumns($model->getTable()));
    $return .= '"},' . PHP_EOL;

    $return .= ' * @OA\Xml(name="' . $className . '"),' . PHP_EOL;

    $factory = null;
    try {
      $factory = $class::factory()->makeOne()->toArray();
    } catch (Throwable $ex) {
    }


    foreach ($this->getColumns($model->getTable()) as $dbColumn) {
      $columnName = $dbColumn->Field;
      $dbType = DB::getSchemaBuilder()->getColumnType($model->getTable(), $columnName);
      $lavelTypes = [
        'bigIncrements' => 'integer',
        'bigInteger' => 'integer',
        'bigint' => 'integer',
        'binary' => 'boolean',
        'boolean' => 'boolean',
        'char' => 'string',
        'dateTimeTz' => 'string',
        'dateTime' => 'string',
        'date' => 'string',
        'decimal' => 'number',
        'double' => 'number',
        'enum' => 'string',
        'float' => 'number',
        'foreignId' => 'integer',
        'foreignIdFor' => 'string',
        'foreignUlid' => 'string',
        'foreignUuid' => 'string',
        'geometryCollection' => 'string',
        'geometry' => 'string',
        'id' => 'integer',
        'increments' => 'integer',
        'integer' => 'integer',
        'ipAddress' => 'string',
        'json' => 'string',
        'jsonb' => 'string',
        'lineString' => 'string',
        'longText' => 'string',
        'macAddress' => 'string',
        'mediumIncrements' => 'integer',
        'mediumInteger' => 'integer',
        'mediumText' => 'string',
        'morphs' => 'string',
        'multiLineString' => 'string',
        'multiPoint' => 'string',
        'multiPolygon' => 'string',
        'nullableMorphs' => 'string',
        'nullableTimestamps' => 'string',
        'nullableUlidMorphs' => 'string',
        'nullableUuidMorphs' => 'string',
        'point' => 'string',
        'polygon' => 'string',
        'rememberToken' => 'string',
        'set' => 'string',
        'smallIncrements' => 'integer',
        'smallInteger' => 'integer',
        'softDeletesTz' => 'string',
        'softDeletes' => 'string',
        'string' => 'string',
        'text' => 'string',
        'timeTz' => 'string',
        'time' => 'string',
        'timestampTz' => 'string',
        'timestamp' => 'string',
        'timestampsTz' => 'string',
        'timestamps' => 'string',
        'tinyIncrements' => 'integer',
        'tinyInteger' => 'integer',
        'tinyText' => 'string',
        'unsignedBigInteger' => 'integer',
        'unsignedDecimal' => 'number',
        'unsignedInteger' => 'integer',
        'unsignedMediumInteger' => 'integer',
        'unsignedSmallInteger' => 'integer',
        'unsignedTinyInteger' => 'integer',
        'ulidMorphs' => 'string',
        'uuidMorphs' => 'string',
        'ulid' => 'string',
        'uuid' => 'string',
        'year' => 'integer',
      ];

      $property = 'property="' . $columnName . '"';

      $resultType = ', type="string"';
      if ($lavelTypes[$dbType] ?? null) {
        $resultType = ', type="' . $lavelTypes[$dbType] . '"';
      }

      $resultExample = '';
      if ($factory && isset($factory[$columnName])) {
        $resultExample = ', example="' . $factory[$columnName] . '"';
      } else if ($columnName == "id") {
        $resultExample = ', example="' . rand(1, 9999) . '"';
      } else if ($columnName == "created_at") {
        $dateTime = new \DateTime('-15 years');
        $interval = DateInterval::createFromDateString(rand(0, 30 * 365 * 24 * 60 * 60) . ' seconds');
        $dateTime->add($interval);
        $resultExample = ', example="' . $dateTime->format('Y-m-d\TH:i:s') . '"';
      } else if ($columnName == "updated_at") {
        $dateTime = new \DateTime('-15 years');
        $interval = DateInterval::createFromDateString(rand(0, 30 * 365 * 24 * 60 * 60) . ' seconds');
        $dateTime->add($interval);
        $resultExample = ', example="' . $dateTime->format('Y-m-d\TH:i:s') . '"';
      } else if ($columnName == "deleted_at") {
        $dateTime = new \DateTime('-15 years');
        $interval = DateInterval::createFromDateString(rand(0, 30 * 365 * 24 * 60 * 60) . ' seconds');
        $dateTime->add($interval);
        $resultExample = ', example="' . $dateTime->format('Y-m-d\TH:i:s') . '"';
      }

      $resultDescription = [];
      if (isset($dbColumn->Key) && trim($dbColumn->Key)) {
        $resultDescription[] =  "key " . $dbColumn->Key;
      }
      if (isset($dbColumn->Default) && $dbColumn->Default) {
        $resultDescription[] = "default " . $dbColumn->Default;
      }
      if (isset($dbColumn->Extra) && $dbColumn->Extra) {
        $resultDescription[] = $dbColumn->Extra;
      }

      $resultDescription = ($resultDescription) ? (', description="' . implode(", ", $resultDescription) . '"') : "";

      $return .= ' * @OA\Property(' . $property .  $resultType . $resultExample . $resultDescription . '),' . PHP_EOL;
    }

    $return .= ' * )' . PHP_EOL;
    $return .= ' *' . PHP_EOL;
    $return .= ' */' . PHP_EOL;

    return $this->writeClassFile($class, $return);
  }

  public function getRequiredColumns(string $table) {
    $columnsInfo = $this->getColumns($table);
    $requiredColumns = [];

    $requiredColumns = array_map(function ($fld) {
      return $fld->Field;
    }, array_filter($columnsInfo, function ($v) {
      return ($v->Null == 'NO' && strpos($v->Extra, "auto_increment") === false);
    }));
    return $requiredColumns;
  }

  public function getColumns(string $table) {
    $connection = config('database.default');
    $driver = config("database.connections.{$connection}.driver");

    if ($driver == 'mysql') {
      $columnsInfo = DB::select('show columns from `' . $table . '`');
      return $columnsInfo;
    } else {
      throw new Exception("Database driver $driver it is not compatible");
    }
  }

  public function writeClassFile(string $class, string $documentationComment) {
    $fileLocal = str_replace("App", "app", $class);
    $fileLocal = str_replace("\\", "/", $fileLocal) . ".php";
    $fileContent = file_get_contents($fileLocal);

    if (!$fileContent) {
      echo "ERROR: file $fileLocal not found" . PHP_EOL;
      return 0;
    }

    if (strpos($fileContent, '* @OA\Schema(') !== false) {
      echo "ERROR: you already have documentation on file $fileLocal. Please remove and rerun this command" . PHP_EOL;
      return 0;
    }

    $fileContent = str_replace("class ", $documentationComment . PHP_EOL . "class ", $fileContent);

    file_put_contents($fileLocal, $fileContent);
    return 0;
  }
}
