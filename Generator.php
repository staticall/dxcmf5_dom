<?php

namespace App\DomainObject;

use App\DomainObject\Manager;
use App\Project\Exception;
use App\Project\File;

class Generator
{
    /** @var Manager */
    protected $dom;

    /** @var array */
    protected $cfg = array();

    /** @var array */
    protected $cache = array();

    /** @var string */
    protected $model_tpl =
        '<?php

namespace App\Project\Model;

/**
<doctype>
 */
class <class> extends \App\Project\Model\Generic\<class>
{
    /** @var string */
    protected $field_prefix = \'<field_prefix>\';
}
';

    /** @var string */
    protected $generic_header_tpl =
        '<?php

namespace App\Project\Model\Generic;

// Connection Component Binding
\Doctrine_Manager::getInstance()->bindComponent(\'\App\Project\Model\<class>\', \'main\');
';

    /** @var string */
    protected $query_tpl =
        '<?php

namespace App\Project\Query;

class <class> extends \App\DomainObject\Query
{
    /**
     * @param int $id
     * @return \App\Project\Model\<class>|null
     */
    public function findById($id)
    {
        $qb = $this->getQueryBuilder()
            ->select(\'<alias>\')
            ->from(\'\App\Project\Model\<class>\', \'<alias>\')
            ->where(\'<alias>.<primary_key> = ?\');

        return $this->getSingleFound($qb, array($id));
    }
}
';

    /**
     * @param Manager $dom
     */
    public function __construct(Manager $dom)
    {
        $cfg = $dom->getConfiguration();
        $cfg['generated']['table_prefix'] = $cfg['connection']['prefix'];

        $this->dom = $dom;
        $this->cfg = $cfg['generated'];
    }

    /**
     * @return void
     */
    public function generateDomainObjects()
    {
        if (!is_writable($this->cfg['generated_path'])) {
            throw new Exception("No write permission '{$this->cfg['generated_path']}'");
        }

        $this->benchmark('START');
        $this->log('Generation began');
        $output_path      = $this->cfg['output_path'];
        $models_path      = $this->cfg['models_path'];
        $queries_path     = $this->cfg['queries_path'];
        $table_prefix     = $this->cfg['table_prefix'];
        $generated_path   = $models_path . DS . 'generated' . DS;
        $partial_path     = $models_path . DS . 'Generic' . DS;

        $options = array(
            'generateTableClasses' => false,
            'generateBaseClasses'  => true,
            'baseClassName'        => '\App\DomainObject\Model',
            'baseClassPrefix'      => '',
            'classPrefix'          => '',
            'classPrefixFiles'     => false,
        );

        File::removeDir($partial_path);
        File::createDir($output_path, 0777);
        File::createDir($models_path, 0777);
        File::createDir($queries_path, 0777);

        $this->generateModels($output_path, $models_path, $table_prefix, $options);

        foreach (File::readDir($generated_path, true) as $item) {
            $class = preg_replace('~\.php~', '', str_replace($generated_path, '', $item));

            $m_file = $models_path . DS . $class . '.php';
            $q_file = $queries_path . DS . $class . '.php';

            if (!is_file($m_file) || strpos(file_get_contents($m_file), 'method') === false) {
                file_put_contents($m_file, $this->generateModelBody($class, $item));
            }

            if (!is_file($q_file)) {
                file_put_contents($q_file, $this->generateQueryBody($class, $item));
            }

            file_put_contents($item, $this->updateGenericBody($class, $item));

            $this->log("\tReady for {$class}");
        }

        File::renameDirOrFile($generated_path, $partial_path);

        foreach (array(dirname($models_path), dirname($queries_path)) as $dirname) {
            File::changeMode($dirname, 0777, true);
        }

        File::removeDir($output_path);
        $this->log('Generation over');
        $this->benchmark('END');
    }

    /**
     * @param string $field
     * @return string
     */
    protected function getFieldName($field)
    {
        return substr(strtolower(preg_replace('~([A-Z])~', '_\1', ucfirst($field))), 1);
    }

    /**
     * @param string $name
     * @param bool   $without_prefix
     * @return string
     */
    protected function getClassName($name, $without_prefix = false)
    {
        return str_replace(ucfirst(str_replace('_', '', $this->cfg['table_prefix'])), $without_prefix ? '' : 'Base', $name);
    }

    /**
     * @param string $class
     * @param string $model
     * @return string
     */
    protected function generateModelBody($class, $model)
    {
        $out = str_replace('<class>', $class, $this->model_tpl);
        $out = str_replace('<field_prefix>', $this->getFieldName($class), $out);
        $out = str_replace('<doctype>', $this->getModelDoctype($class, $model), $out);

        return $out;
    }

    /**
     * @param string $class
     * @param string $model
     * @return string
     */
    protected function updateGenericBody($class, $model)
    {
        $model_body = file_get_contents($model);

        $array = preg_split("/\n/", $model_body);

        $out = str_replace('<class>', $class, $this->generic_header_tpl);
        $out .= implode(PHP_EOL, array_slice($array, 3));

        return $out;
    }

    /**
     * @param string $class
     * @param string $model
     * @return string
     */
    protected function generateQueryBody($class, $model)
    {
        $alias = strtolower(preg_replace('~[a-z]+~', '', $class));

        $out = str_replace('<class>', $class, $this->query_tpl);
        $out = str_replace('<alias>', $alias, $out);

        $model_body = file_get_contents($model);

        $res = preg_split("~'primary'\s*=>\s*true~", $model_body);
        $m   = array();
        if (count($res) && preg_match("~.*hasColumn\('([^',]+).*~", $res[0], $m)) {
            $out = str_replace('<primary_key>', $m[1], $out);
        }

        return $out;
    }

    /**
     * @param $class
     * @param $model
     * @return string
     */
    protected function getDoctypeMethods($class, $model)
    {
        if (!empty($this->cache[$class]) && !empty($this->cache[$class]['doctype_methods'])) {
            return $this->cache[$class]['doctype_methods'];
        } else {
            $this->cache[$class] = array('doctype_methods' => null);
        }

        $prefix    = $class;
        $prefix{0} = strtolower($prefix{0});
        $prefix    = strtolower(preg_replace('~([A-Z])+~', '_$1', $prefix));

        $m = preg_replace('~^.*?(@property)~', '$1', str_replace("\n", '', file_get_contents($model)));
        $m = preg_replace('~@package.*~', '', $m);

        $getters = array();
        $setters = array();

        $res = preg_split('~\s*\*\s*~', $m);
        foreach ($res as $v) {
            $v = preg_replace('~@property\s*~', '', $v);
            $v = explode(' ', $v);

            if (count($v) == 1) {
                continue;
            }

            $name = str_replace("{$prefix}_", '', str_replace('$', '', $v[1]));
            $name = explode('_', $name);

            foreach ($name as $k => $c) {
                $c{0}     = strtoupper($c{0});
                $name[$k] = $c;
            }

            $name = implode('', $name);

            $set = " set{$name}(%s \$arg)";
            $get = " %s get{$name}";

            $type = null;
            if (strpos($v[0], 'DomainObject\Model') !== false) {
                $type = '\App\DomainObject\Model\\' . $name;
            } else {
                switch ($v[0]) {
                    case 'integer' :
                        $type = 'int';
                        break;
                    case 'decimal' :
                        $type = 'float';
                        break;
                    case 'float' :
                        $type = 'float';
                        break;
                    case 'bool' :
                        $type = 'bool';
                        break;
                    case 'string' :
                        $type = 'string';
                        break;
                    case 'enum' :
                        $type = 'string';
                        break;
                    case 'timestamp' :
                        $type = 'DateTime';
                        break;
                    case 'date' :
                        $type = 'DateTime';
                        break;
                    case 'Doctrine_Collection' :
                        $type = '\App\DomainObject\Model\\' . $name . '[]';
                        $get .= 's';
                        $set = null;
                        break;
                }
            }

            $getters[] = ' * @method ' . sprintf("{$get}()", $type);
            $set ? $setters[] = ' * @method ' . sprintf($set, $type) : false;
        }

        $getters[] = " * \n";

        return $this->cache[$class]['doctype_methods'] = implode("\n", $getters) . implode("\n", $setters);
    }

    /**
     * @param string $class
     * @param string $model
     * @return string
     */
    protected function getModelDoctype($class, $model)
    {
        return $this->getDoctypeMethods($class, $model);
    }

    /**
     * @param string $output_path
     * @param null   $table_prefix
     */
    protected function addClassNames($output_path, $table_prefix = null)
    {
        $temp_file_path = $output_path . '.old';

        File::renameDirOrFile($output_path, $temp_file_path);

        $temp_file = fopen($temp_file_path, 'r');
        $yaml_file = fopen($output_path, 'w');

        $table_prefix = ucfirst(preg_replace('~(_)+~', '_', $table_prefix));

        while (!feof($temp_file)) {
            $line = fgets($temp_file);

            if (strpos($line, $table_prefix) !== false) {
                $new_line = str_replace($table_prefix, '', $line);
                fwrite($yaml_file, $new_line);
            } else {
                fwrite($yaml_file, $line);
            }
        }

        fclose($temp_file);
        fclose($yaml_file);
    }

    /**
     * @param string $output_path
     * @param string $models_path
     * @param null   $table_prefix
     * @param array  $options
     */
    protected function generateModels($output_path, $models_path, $table_prefix = null, $options = array())
    {
        $output_path .= DS . 'database.yml';

        \Doctrine_Core::generateYamlFromDb($output_path);

        $this->addClassNames($output_path, $table_prefix);

        \Doctrine_Core::generateModelsFromYaml($output_path, $models_path, $options);
    }

    /**
     * @param string $hook
     */
    protected function benchmark($hook = 'START')
    {
        $memory = File::sizeReadable(memory_get_usage(true));
        if ($hook == 'START') {
            $this->benchmark = array(
                'start' => microtime(true),
                'memory' => $memory,
            );
            $this->log("### BENCHMARK START");
            $this->log("### memory: {$memory['size']} {$memory['size_unit']}, ({$memory['size_in_bytes']})");
            $this->log("### memory limit: " . ini_get('memory_limit'));
        } elseif ($hook == 'END') {
            $this->log("### BENCHMARK END");
            $this->log("### memory: {$memory['size']} {$memory['size_unit']}, ({$memory['size_in_bytes']})");
            $usage = File::sizeReadable(memory_get_usage(true) - $this->benchmark['memory']['size_in_bytes']);
            $peak = File::sizeReadable(memory_get_peak_usage(true));
            $this->log("### memory usage: {$usage['size']} {$usage['size_unit']}, ({$usage['size_in_bytes']})");
            $this->log("### memory peak: {$peak['size']} {$peak['size_unit']}, ({$peak['size_in_bytes']})");
            $this->log("### time: " . (microtime(true) - $this->benchmark['start']));
        }
    }

    /**
     * @param $message
     * @param null $mode
     */
    protected function log($message, $mode = null)
    {
        print $message . PHP_EOL;
    }
}