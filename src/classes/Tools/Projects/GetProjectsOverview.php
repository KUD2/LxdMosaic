<?php

namespace dhope0000\LXDClient\Tools\Projects;

use dhope0000\LXDClient\Tools\Universe;

class GetProjectsOverview
{
    public function __construct(Universe $universe)
    {
        $this->universe = $universe;
    }

    //Based on https://github.com/lxc/lxd/issues/7946#issuecomment-703367651
    public function get($userId)
    {
        $clustersAndStandalone = $this->universe->getEntitiesUserHasAccesTo($userId, "projects");

        foreach ($clustersAndStandalone["clusters"] as $cluster) {
            foreach ($cluster["members"] as $member) {
                $this->addSchedulesToSchedule($member);
            }
        }

        foreach ($clustersAndStandalone["standalone"]["members"] as $member) {
            if (!$member->hostOnline()) {
                continue;
            }

            $hostProjects = [];

            foreach ($member->getCustomProp("projects") as $project) {
                $projectDetails = $member->projects->info($project, 2);
                $limits = $this->getLimitValues($projectDetails["config"]);
                $member->setProject($project);
                $instances = $member->instances->all(2);

                foreach ($instances as $instance) {
                    if ($instance["type"] == "virtual-machine") {
                        $limits["limits.virtual-machine"]["value"]++;
                    } else {
                        $limits["limits.containers"]["value"]++;
                    }

                    $limits["limits.memory"]["value"] += $instance["state"]["memory"]["usage"];
                    $limits["limits.processes"]["value"] += $instance["state"]["processes"];
                    $limits["limits.cpu"]["value"] += $instance["state"]["cpu"]["usage"];

                    //TODO https://github.com/lxc/lxd/issues/8173
                    if ($instance["state"]["disk"] != null) {
                        $limits["limits.disk"]["value"] += $instance["state"]["disk"]["root"]["usage"];
                    }
                }
                $limits["limits.networks"]["value"] = count($member->networks->all());
                $images = $member->images->all(2);
                $limits["limits.disk"]["value"] += array_sum(array_column($images, "size"));
                $hostProjects[$project] = $limits;
            }
            $member->setCustomProp("projects", $hostProjects);
        }
        return $clustersAndStandalone;
    }

    private function getLimitValues($config)
    {
        $expectedKeys = [
            "limits.containers"=>["limit"=>null, "value"=>0],
            "limits.cpu"=>["limit"=>null, "value"=>0],
            "limits.disk"=>["limit"=>null, "value"=>0],
            "limits.memory"=>["limit"=>null, "value"=>0],
            "limits.networks"=>["limit"=>null, "value"=>0],
            "limits.processes"=>["limit"=>null, "value"=>0],
            "limits.virtual-machine"=>["limit"=>null, "value"=>0],
        ];

        foreach ($expectedKeys as $key=>$details) {
            $expectedKeys[$key]["limit"] = isset($config[$key]) ? $config[$key] : null;
        }
        return $expectedKeys;
    }
}
