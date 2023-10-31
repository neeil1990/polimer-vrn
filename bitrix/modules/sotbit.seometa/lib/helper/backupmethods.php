<?php

namespace Sotbit\Seometa\Helper;

class BackupMethods
{
    private $backupFileName = 'sitemap_seometa_';
    private $dir = '';
    private $arrFiles = [];

    public function makeBackup(
        $dir
    ) {
        $empty = true;
        if (is_dir($dir)) {
            if (($res = opendir($dir))) {
                while (($item = readdir($res))) {
                    if ($item == '..' || $item == '.') {
                        continue;
                    }

                    if (mb_strpos($item,
                            $this->backupFileName) !== false) {
                        $empty = false;
                        $date = date('m-d-y_H-i');
                        $pathinfo = pathinfo($dir . $item);
                        $newName = $pathinfo['filename'] . '_' . $date . '.' . $pathinfo['extension'];
                        $archiveTmpDir = $dir .'upload/seometa_archive/';
                        if(!is_dir($archiveTmpDir)){
                            mkdir($archiveTmpDir, 0755);
                        }
                        rename($dir . $item, $archiveTmpDir. $newName);
                        self::addFile($archiveTmpDir . $newName);
                    }
                }

                if(!$empty) {
                    self::setDir($dir);
                }
                closedir($res);
            }
        }

        $result = self::archivePacking();

        return $result;
    }

    private function addFile(
        $fileName
    ) {
        if (is_file($fileName) && !in_array($fileName, $this->arrFiles)) {
            $this->arrFiles[] = $fileName;
        }
    }

    private function setDir(
        $dir
    ) {
        if ($dir) {
            $this->dir = $dir;
        }
    }

    private function checkStatus(
        $archiveObject
    ) {
        $result = '';
        switch ($archiveObject)
        {
            case \IBXArchive::StatusSuccess:
                //OK
                break;
            case \IBXArchive::StatusError:
                //Not ok
                $result = $archiveObject->GetErrors();
                break;
        }

        return $result;
    }

    private function archivePacking(
    ) {
        if ($this->arrFiles) {
            $name = 'seometasitemap_backup.zip';
            $archiveObject = \CBXArchive::GetArchive($this->dir . $name);

            if($archiveObject instanceof \IBXArchive) {
                $archiveObject->SetOptions(
                    [
                        "REMOVE_PATH" => $_SERVER["DOCUMENT_ROOT"],
                        "UNPACK_REPLACE" => true
                    ]
                );

                $archiveTmpDir = $this->dir  . 'upload/seometa_archive/';

                $uRes = $archiveObject->Unpack($archiveTmpDir);

                $archiveObject->SetOptions(
                    [
                        "COMPRESS" => true,
                        "ADD_PATH" => false,
                        "REMOVE_PATH" => $archiveTmpDir,
                    ]
                );

                if (is_dir($archiveTmpDir)) {
                    if (($res = opendir($archiveTmpDir))) {
                        while (($item = readdir($res))) {
                            if ($item == '..' || $item == '.') {
                                continue;
                            }

                            if (mb_strpos($item,
                                    $this->backupFileName) !== false) {
                                self::addFile($archiveTmpDir . $item);
                            }
                        }
                        closedir($res);
                    }
                }

                $archiveObject = $archiveObject->Pack($this->arrFiles);

                self::delDir($archiveTmpDir);

                $result = self::checkStatus($archiveObject);
            }
        } else {
            //TODO: if not have a files
        }

        return $result;
    }

    static function delDir($dir) {
        $files = array_diff(scandir($dir), ['.','..']);
        foreach ($files as $file) {
            (is_dir($dir.'/'.$file)) ? self::delDir($dir.'/'.$file) : unlink($dir.'/'.$file);
        }
        return rmdir($dir);
    }
}
