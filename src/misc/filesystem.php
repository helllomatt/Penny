<?php

namespace Penny;

class FileSystem {
    /**
     * Scans a folder for it's contencts
     *
     * @param  string $folder
     * @param  array  $options
     * @return array
     */
    public static function scan($folder, $options = []) {
        if (!file_exists($folder)) throw new FileSystemException('Folder doesn\'t exist.');
        if (!is_dir($folder)) throw new FileSystemException('Given folder is not a folder.');

        if (!isset($options['base'])) $options['base'] = $folder;

        $ret = [];

        $files = scandir($folder);
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) continue;
            if (is_dir($folder.'/'.$file) && isset($options['recursive']) && $options['recursive'] == true) {
                if (isset($options['flat']) && $options['flat'] == true) {
                    $ret[] = $folder."/".$file.'/';
                    $ret = array_merge($ret, static::scan($folder.'/'.$file, array_merge($options, ['prefix' => true])));
                } else {
                    $ret[$file] = static::scan($folder.'/'.$file, $options);
                }
            } else {
                if (isset($options['prefix']) && $options['prefix'] == true) {
                    $ret[] = str_replace($options['base'].'/', '', $folder).'/'.$file;
                } elseif (is_dir($folder.'/'.$file)) $ret[] = $file.'/';
                else $ret[] = $file;
            }
        }

        return $ret;
    }

    /**
     * Finds the namespace of a php file
     *
     * @param  string $file
     * @return string
     */
    public static function findNamespace($file) {
        $handle = fopen($file, 'r');
        if (!$handle) {
            throw new FileSystemException('File couldn\t be opened.');
        } else {
            $namespace = null;
            while (($line = fgets($handle)) !== false) {
                if (strpos($line, 'namespace') !== false) {
                    $namespace = str_replace(['namespace ', ';', PHP_EOL], '', $line);
                    break;
                }
            }

            fclose($handle);

            if (!$namespace) throw new FileSystemException('No namespace exists in file '.$file);
            else return $namespace;
        }
    }

    /**
     * Gets the extension of a file
     *
     * @param  string $file
     * @return string
     */
    public static function getExtension($file) {
        $info = new \SplFileInfo($file);
        return $info->getExtension();
    }

    public static function copy($source, $dest, $permissions = 0755) {
        if (is_link($source)) return symlink(readlink($source), $dest);
        if (is_file($source)) return copy($source, $dest);
        if (!is_dir($dest)) mkdir($dest, $permissions);

        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            if ($entry == '.' || $entry == '..') continue;

            static::copy($source."/".$entry, $dest."/".$entry, $permissions);
        }

        $dir->close();
        return true;
    }
}
