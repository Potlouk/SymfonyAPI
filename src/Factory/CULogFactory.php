<?php

namespace App\Factory;

use App\Entity\CULog;
use App\Entity\Document;
use App\Entity\Report;
use App\Entity\Template;

final class CULogFactory {

    public static function build(string $action, string $email, object $attachment): CULog {
        $cuLog = (new CULog())
        ->setAction($action)
        ->setMadeBy($email);

        self::attachObject($cuLog, $attachment);

        return $cuLog;
    }

    private static function attachObject(CULog $log, object $attachment): void {
        if ($attachment instanceof Document)
            $log->setDocument($attachment);

        if ($attachment instanceof Report)
            $log->setReport($attachment);

        if ($attachment instanceof Template)
            $log->setTemplate($attachment);
    }
}