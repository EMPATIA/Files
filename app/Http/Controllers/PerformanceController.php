<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\One\One;
use Exception;

class PerformanceController extends Controller
{
    public function getCpuData(){

        $cmdNCpus = 'cat /proc/cpuinfo | grep processor | wc -l | cut -c 1 ';
        $nCpus = shell_exec($cmdNCpus);
        // echo('Numero total de CPUs: '.$nCpus);
        $cpu = array();
        $cmdCpu = ' cat /proc/stat | grep cpu | tr -s \' \' \'-\' | cut -d\'-\' -f2,3,4,5';
        $cpuAll = shell_exec($cmdCpu);
        $data = str_replace("\n", ";", $cpuAll);
        $cpuPrevResult = explode(";", $data);

        for ($i = 1; $i <= $nCpus; $i++) {
            list($userPrev[$i], $nicePrev[$i], $systemPrev[$i], $idlePrev[$i]) = explode("-", $cpuPrevResult[$i]);
        }

        sleep(1); //the result is a difference

        $cmdCpu = ' cat /proc/stat | grep cpu | tr -s \' \' \'-\' | cut -d\'-\' -f2,3,4,5';
        $cpuAll2 = shell_exec($cmdCpu);
        $data2 = str_replace("\n", ";", $cpuAll2);
        $cpuResult = explode(";", $data2);

        for ($i = 1; $i <= $nCpus; $i++) {

            list($userFinal, $niceFinal, $systemFinal, $idleFinal) = explode("-", $cpuResult[$i]);
            $user = $userFinal - $userPrev[$i];
            $nice = $niceFinal - $nicePrev[$i];
            $system = $systemFinal - $systemPrev[$i];
            $idle = $idleFinal - $idlePrev[$i];

            $total = $user + $nice + $system + $idle;
            $userPerc = ($user / $total) * 100;
            $systemPerc = ($system / $total) * 100;
            $nicePerc = ($nice / $total) * 100;
            $idlePerc = ($idle / $total) * 100;

            $cpu[$i]['value'] = $userPerc+$systemPerc+$nicePerc;
            $cpu[$i]['user'] = $userPerc;   //cpu[0] is the general cpu, all cpus aggregated
            $cpu[$i]['nice'] = $nicePerc;      //cp[1] is cpu number 1, there's nCpus
            $cpu[$i]['system'] = $systemPerc;
            $cpu[$i]['idle'] = $idlePerc;
        }

        return $cpu;
    }


    public function getIoData(){
        $cmdGetNameBlock = 'cat /proc/diskstats | tr -s \' \' \'-\' | cut -d- -f4';
        $namesBlocks = str_replace("\n"," ", shell_exec($cmdGetNameBlock));
        $block = explode(" ", $namesBlocks);

        $cmdGetIo = 'cat /proc/diskstats | tr -s \' \' \'-\' | cut -d\'-\' -f5,7,8,9,11,12';
        $cmdResult = shell_exec($cmdGetIo);
        $data = str_replace("\n", ";", $cmdResult);
        $dataTemp = explode(";", $data);
        $readTotal=[];$readSector=[];$readTime=[];$writeTotal=[];$writeSector=[];$writeTime=[];
        $readTotalCont=0;$readSectorCont=0;$readTimeCont=0;$writeTotalCont=0;$writeSectorCont=0;$writeTimeCont=0;

        for ($i = 0; $i <= (count($dataTemp)-2); $i++) {

            list($readTotal[$i], $readSector[$i], $readTime[$i], $writeTotal[$i], $writeSector[$i], $writeTime[$i]) = explode("-", $dataTemp[$i]);
            $readTotalCont=$readTotalCont+$readTotal[$i];
            $readSectorCont=$readSectorCont+$readSector[$i];
            $readTimeCont=$readTimeCont+$readTime[$i];
            $writeTotalCont=$writeTotalCont+$writeTotal[$i];
            $writeSectorCont=$writeSectorCont+$writeSector[$i];
            $writeTimeCont=$writeTimeCont+$writeTime[$i];
        }

        try {
            $readSectorTime = $readSectorCont / $readTimeCont;
            $readByteTime = ($readTotalCont / $readTimeCont) * 512;
        }catch(Exception $e){
            $readSectorTime =0;
            $readByteTime =0;
        }

        try{
            $writeSectorTime = $writeSectorCont / $writeTimeCont;
            $writeByteTime = ($writeTotalCont / $writeTimeCont)*512;
        }catch(Exception $e){
            $writeSectorTime = 0;
            $writeByteTime =0;
        }

        $io['readSector']=$readSectorTime;
        $io['readByte']=$readByteTime;
        $io['writeSector']=$writeSectorTime;
        $io['writeByte']=$writeByteTime;

        return $io;
    }

    public function getMemoryData(){

        $cmdMemory = 'free -m -t | grep Mem: | tr -s \' \' \'-\'';
        $memory2 = shell_exec($cmdMemory);
        $memory = str_replace("\n", ";", $memory2);
        $memoryResult = explode(";", $memory);

        list($f,$memoryTotal, $memoryUsed, $memoryFree, $memoryShared, $memoryBuffers, $memoryCached) = explode("-", $memoryResult[0]);

        return $memoryUsed;
    }


    public function saveDataToDB($cpu, $ioData, $memoryUsed){

        $response = One::Post([
            'url' => 'http://catiasma.empatia-dev.onesource.pt:5006',
            'component' => 'logs',
            'api' => 'PerformanceController',
            'method' => 'saveDataToDB',
            'params' => ["cpu" => $cpu,
                "memory" => $memoryUsed,
                "io"=> $ioData]

        ]);
        dd($response);

    }


    public function getDataPerformanceAndSave(){

        $cpu = $this->getCpuData();
        $ioData = $this->getIoData();
        $memoryUsed= $this->getMemoryData();

        PerformanceController::saveDataToDB($cpu, $ioData, $memoryUsed);

    }
}