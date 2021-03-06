<?php

namespace dhope0000\LXDClient\Controllers\User\Dashboard\Graphs;

use dhope0000\LXDClient\Tools\User\Dashboard\Graphs\DeleteGraph;

class DeleteGraphController
{
    private $deleteGraph;

    public function __construct(DeleteGraph $deleteGraph)
    {
        $this->deleteGraph = $deleteGraph;
    }

    public function delete(int $userId, int $graphId)
    {
        $this->deleteGraph->delete($userId, $graphId);
        return ["state"=>"success", "message"=>"Delete Graph"];
    }
}
