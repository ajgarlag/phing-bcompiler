<?php
/**
 * phing-bcompiler
 * Copyright (C) 2011 Antonio J. García Lagar <aj@garcialagar.es>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (C) 2011 Antonio J. García Lagar <aj@garcialagar.es>
 * @license    http://www.gnu.org/licenses/lgpl-3.0.html LGPL3
 */
require_once "phing/Task.php";
/**
 * Task to compile php filesets
 * 
 * @copyright  Copyright (C) 2011 Antonio J. García Lagar <aj@garcialagar.es>
 * @license    http://www.gnu.org/licenses/lgpl-3.0.html LGPL3
 */
class BcompilerTask extends Task {

    protected $toDir = null;   // the destination dir (from xml attribute)
    protected $filesets = array(); // all fileset objects assigned to this task

    /**
     * Set the toDir. We have to manually take care of the
     * type that is coming due to limited type support in php
     * in and convert it manually if neccessary.
     *
     * @param  string/object  The directory, either a string or an PhingFile object
     * @return void
     * @access public
     */

    function setTodir(PhingFile $dir) {
        $this->toDir = $dir;
    }

    /**
     * Nested creator, creates a FileSet for this task
     *
     * @access  public
     * @return  object  The created fileset object
     */
    function createFileSet() {
        $num = array_push($this->filesets, new FileSet());
        return $this->filesets[$num - 1];
    }

    /**
     * The main entry point method.
     */
    public function main() {
        // deal with the filesets
        foreach ($this->filesets as $fs) {

            $ds = $fs->getDirectoryScanner($this->getProject());
            $fromDir = $fs->getDir($this->getProject());

            $srcFiles = $ds->getIncludedFiles();
            $srcDirs = $ds->getIncludedDirectories();
            
            foreach ($srcFiles as $file) {
                $inputPath = $fromDir->getAbsolutePath() . DIRECTORY_SEPARATOR . $file;
                $outputPath = $this->toDir->getAbsolutePath(). DIRECTORY_SEPARATOR . $file;
                $this->_bencodeFile($inputPath, $outputPath);
            }
        }
    }
    
    /**
     * Compiles the file $inputPath to the $outputPath
     * @param string $inputPath
     * @param string $outputPath 
     * @return void
     */
    protected function _bencodeFile($inputPath, $outputPath) {
        $outputDir = dirname($outputPath);
        $d = new PhingFile((string) $outputDir);
        if (!$d->exists()) {
            if (!$d->mkdirs()) {
                $this->log("Unable to create directory " . $d->__toString(), Project::MSG_ERR);
            }
        }
        if (!$fp = fopen($outputPath, 'w')) {
            $this->log("Unable to open file $outputPath in write mode", Project::MSG_ERR);
        }
        if (function_exists('bcompiler_set_filename_handler')) {
            bcompiler_set_filename_handler('basename');
        }
        if (!bcompiler_write_header($fp)) {
            $this->log("Unable write file header for $outputPath file", Project::MSG_ERR);
        }
        if (!bcompiler_write_file($fp, $inputPath)) {
            $this->log("Unable write file $inputPath into $outputPath file", Project::MSG_ERR);
        }
        if (!bcompiler_write_footer($fp)) {
            $this->log("Unable write file footer for $outputPath file", Project::MSG_ERR);
        }
    }
}