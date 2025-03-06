<?php
// ################################################################
// ________________________________________________________________
/*
    < Manpower Deployment >

    This is not yet fully automated.

    Put Files on 'stage' folder for automatic versioning and redirecting.
    Then put 'DONE.txt' file on the 'stage' as a sign of 'Upload completed.'
    to avoid versioning incomplete file set.
    
    After putting 'DONE.txt' file,
    this will rename stage folder to a version folder, 
    and serve it to visitors.
    
    Don't forget 'DONE.txt'
    
    
    - 250304 / Junghoon Lee (lee62113@naver.com / ljhbunker.com)";
*/



// ################################################################
// ________________________________________________________________
// [CORE]
// Organization Structure
/* 
    [Core]
    * VersionRedirector

        [READY]
        * StageFolderManager
            * ErrorHandler

        [START]
        * LatestFolderResolver

        [END]
        * Sender

*/

date_default_timezone_set('Asia/Seoul');
$parentPath = __DIR__;


class VersionRedirector {
    public $higherTeam;
    public $StageFolderManager;
    public $ErrorHandler;
    public $LatestFolderResolver;
    public $Sender;

    public $parentPath;

    public function __construct($higherTeam, $parentPath) {
        $this->higherTeam = $higherTeam;
        $this->parentPath = $parentPath;
        $this->StageFolderManager = new StageFolderManager($this, $parentPath);
        $this->ErrorHandler = new ErrorHandler($this, $parentPath);
        $this->LatestFolderResolver = new LatestFolderResolver($this, $parentPath);
        $this->Sender = new Sender($this, $parentPath);
    }

    
    public function redirect(){
        // CORE
        $redirected = null;

        // READY
        $check_update = $this->StageFolderManager->manage_stageFolder();
        if ($check_update) {
            $lockHandle = $check_update;
            $mainTain_folders = $this->ErrorHandler->maintain($lockHandle);
        }

        // START
        $set_destination = $this->LatestFolderResolver->set_newest();
        if (!$set_destination) {
            $welcomeMsg = "
            <br><h2> < Manpower Deployment > </h2>
            <br><b> Setup Completed! </b>
            <br>
            <br> Put Files on 'stage' folder for automatic versioning and redirecting.
            <br> Then put 'DONE.txt' file on the 'stage' as a sign of 'Upload completed.'
            <br> to avoid versioning incomplete file set.
            <br> 
            <br> After putting 'DONE.txt' file,
            <br> this will rename stage folder to a version folder, 
            <br> and serve it to visitors.
            <br> 
            <br> Don't forget 'DONE.txt'
            <br> 
            <br> 
            <br> - 250304 / Junghoon Lee (lee62113@naver.com / ljhbunker.com)";

            echo $welcomeMsg;
            return false;
        }

        // END
        $newestFolder = $set_destination;
        $redirect = $this->Sender->sendLatestFolder($newestFolder);
    }

}

try {
    (new VersionRedirector(null, $parentPath))->redirect();
}
catch (Exception $e){
    echo "Uploading new version...";
}



// ################################################################
// ________________________________________________________________
// [READY]
class StageFolderManager {
    public $higherTeam;

    private $parentPath;
    private $stagePath;
    private $newestFile;
    private $lockFile;
    private $doneFile;
    private $doneFileNeeded;

    public function __construct($higherTeam, $parentPath) {
        $this->higherTeam = $higherTeam;
        $this->parentPath = $parentPath;
        $this->stagePath = "$parentPath/stage";
        $this->newestFile = "$parentPath/newest.txt";
        $this->lockFile = "$parentPath/stage.lock";
        $this->doneFile = "$this->stagePath/DONE.txt";
        $this->doneFileNeeded = "$this->stagePath/DONE_txt_fileNeededToProcess.txt";
    }

    public function manage_stageFolder(){
        // CORE
        $stageFolderManaged = null;

        // READY
        // Lock
        $lockHandle = $this->lock();
        if (!$lockHandle) {
            return;
        }

        $newestFile = file_exists($this->newestFile);
        if (!$newestFile) {
            file_put_contents($this->newestFile, "");
        }

        $stageFolderCheck = is_dir($this->stagePath);
        if (!$stageFolderCheck) {
            return $this->setupStageFolder();
        }

        $doneFileCheck = file_exists($this->doneFile);
        if (!$doneFileCheck) {
            return;
        }
                
        // START
        $newVersionName = $this->generateVersionFolderName();
        rename($this->stagePath, "$this->parentPath/" . $newVersionName);
        $this->fullSetupStageFolderFile();
        file_put_contents($this->newestFile, $newVersionName);

        // END
        $stageFolderManaged = $lockHandle;
        return $stageFolderManaged;
    }


    public function setupStageFolder() {
        mkdir($this->stagePath);
        file_put_contents($this->doneFileNeeded, "");
        return;
    }


    public function lock() {
        $lockHandle = fopen($this->lockFile, 'w');
        if (!$lockHandle) {
            return false;
        }
        if (!flock($lockHandle, LOCK_EX)) {
            fclose($lockHandle);
            return false;
        }
        return $lockHandle;
    }


    public function generateVersionFolderName() {
        return 'v' . date('ymd-His');
    }


    public function fullSetupStageFolderFile(){
        $stageFolderCheck = is_dir($this->stagePath);
        if (!$stageFolderCheck) {
            return $this->setupStageFolder();
        }
    }
}


class ErrorHandler {
    public $higherTeam;

    private $parentPath;
    private $newestFile;
    private $lockFile;

    public function __construct($higherTeam, $parentPath) {
        $this->higherTeam = $higherTeam;
        $this->parentPath = $parentPath;
        $this->newestFile = "$parentPath/newest.txt";
        $this->lockFile = "$parentPath/stage.lock";
    }


    public function maintain($lockHandle) {
        // CORE
        $foldersMaintained = null;

        // READY
        $allFolders = $this->getFolders();
        $newest = file_get_contents($this->newestFile);

        // START
        foreach ($allFolders as $folder) {
            if (strcmp(basename($folder), $newest) > 0) {
                $this->deleteFolder($folder);
            }    
        }

        // END
        $this->unlock($lockHandle);
        $foldersMaintained = $newest;
        return $foldersMaintained;
    }


    public function getFolders() {
        $folders = glob("$this->parentPath/v*", GLOB_ONLYDIR);
        $validFolders = array_filter($folders, function ($folder) {
            return preg_match('/v\d{6}-\d{6}$/', basename($folder));
        });
        return $validFolders;
    }


    private function deleteFolder($folder) {
        foreach (glob("$folder/*") as $file) {
            is_dir($file) ? $this->deleteFolder($file) : unlink($file);
        }
        rmdir($folder);
    }


    public function unlock($lockHandle) {
        if ($lockHandle) {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            unlink($this->lockFile);
        }
    }

}



// ################################################################
// ________________________________________________________________
// [START]
class LatestFolderResolver {
    public $higherTeam;

    private $parentPath;
    private $stageManager;
    private $errorHandler;
    private $newestFile;


    public function __construct($higherTeam, $parentPath) {
        $this->higherTeam = $higherTeam;
        $this->parentPath = $parentPath;
        $this->stageManager = $this->higherTeam->StageFolderManager;
        $this->errorHandler = $this->higherTeam->ErrorHandler;
        $this->newestFile = "$parentPath/newest.txt";
    }

    public function set_newest(){
        // CORE
        $newestTxtFileDetected = null;

        // READY
        $check_newestContent = $this->deepAnalyze();
        if ($check_newestContent) {
            $fullApprovedFolderName = $check_newestContent;
            return $fullApprovedFolderName;
        }

        // START
        $allFolders = $this->errorHandler->getFolders();
        if (!count($allFolders)){
            return false;
        }
        $allFolders = $this->uSorter($allFolders);
        $latestFolder = $allFolders[0];
        file_put_contents($this->newestFile, basename($latestFolder));

        // END
        $newestTxtFileDetected = basename($latestFolder);
        return $newestTxtFileDetected;
    }


    public function deepAnalyze(){
        // CORE
        $fullApproved = null;

        // READY
        $check_existence = file_exists($this->newestFile);
        if (!$check_existence){ return false;}

        $check_contentExistence = file_get_contents($this->newestFile);
        if (!$check_contentExistence) { return false;}

        $folderName = $check_contentExistence;
        $check_contentValidation = preg_match('/^v\d{6}-\d{6}$/', $folderName);
        if (!$check_contentValidation) { return false;}

        // START
        $check_folderExistence = file_exists($this->parentPath . '/' . $folderName);
        if (!$check_folderExistence) {return false;}

        // END
        $fullApproved = $folderName;
        return $fullApproved;
    }


    public function uSorter($array){
        usort($array, fn($a, $b) => strcmp($b, $a)); // Sort descending
        return $array;
    }
}



// ################################################################
// ________________________________________________________________
// [END]
class Sender {
    public $parentPath;
    public $higherTeam;

    public function __construct($higherTeam, $parentPath) {
        $this->higherTeam = $higherTeam;
        $this->parentPath = $parentPath;
    }

    public function sendLatestFolder($latestFolderName) {
        if (!$latestFolderName) {
            echo 'Updating...';
            return false;
        }

        // CORE
        $sent = null;

        // READY
        $pageFile = $this->getPageFile($latestFolderName);
        if (!$pageFile) {
            die("No page file found: index.php or index.html");
        }

        $pageContent = file_get_contents($pageFile);

        // START
        $baseUrl = "$latestFolderName/";
        if (!preg_match('/<base /i', $pageContent)) {
            $pageContent = preg_replace('/<head\b[^>]*>/i', '$0<base href="' . $baseUrl . '">', $pageContent, 1);
        }

        // END
        $beforeSendingJobs = [
            function() {$this->counterSetting(); }, 
        ];
        foreach ($beforeSendingJobs as $job) {
            $job();
        }

        echo $pageContent;
    }


    public function getPageFile($latestFolderName){
        // CORE
        $mainFileFound = null;

        // READY
        $folderPath = "$this->parentPath/$latestFolderName";

        // START
        $indexPhp = "$folderPath/index.php";
        $indexHtml = "$folderPath/index.html";

        if (file_exists($indexPhp)) {
            return $indexPhp;
        }
        elseif (file_exists($indexHtml)) {
            return $indexHtml;
        }        

        // END
        $mainFileFound = false;
        return $mainFileFound;
    }

// ________________________________________________________________
// [Additional Jobs]

    public function counterSetting(){
        // _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ [ Counter ]
        // _CORE
        $counted = null;

        // _READY
        $countFile = 'visitor_count.txt';
        if (!file_exists($countFile)) {
            file_put_contents($countFile, "0");
        }
        
        $file = fopen($countFile, 'c+');

        // _START
        if (flock($file, LOCK_EX)) {
            rewind($file);
            $count = (int) fgets($file);
            $count++;

            ftruncate($file, 0);
            rewind($file);  
            fwrite($file, $count);

            fflush($file);
            flock($file, LOCK_UN);
        }

        fclose($file);

        // _END
        $counted = true;
        // _ _ _ _ _ _ _ _ _ _ _ _ _ _ _//

        // _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ [ Counter Viewer ]
        $newPhpFile = 'display_count.php';

        if (!file_exists($newPhpFile)) {
        $phpContent = <<<'PHP'
        <?php
        // GOAL
        $countDisplayed = null;

        // READY
        $countFile = 'visitor_count.txt';
        $file = fopen($countFile, 'r');

        // START
        $count = null;
        if (flock($file, LOCK_SH)) {
            $count = fread($file, filesize($countFile));
            flock($file, LOCK_UN);
        }

        fclose($file);
        echo '<p style="font-size: 5vh">'. $count. '</p>';

        // END
        $countDisplayed = true;
        ?>
        PHP;

        file_put_contents($newPhpFile, $phpContent);
        }
        // _ _ _ _ _ _ _ _ _ _ _ _ _ _ _//

    }
}
