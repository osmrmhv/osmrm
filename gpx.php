<?php
/*
    This file is part of OSM Route Manager.

    OSM Route Manager is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OSM Route Manager is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OSM Route Manager.  If not, see <http://www.gnu.org/licenses/>.
*/

	require("include.php");

	header("Content-type: text/xml; charset=UTF-8", true);
	header("Content-disposition: attachment; filename=route.gpx");

	if(isset($_GET["relation"]))
	{
		$segments = OSMRelation::segmentate($_GET["relation"]);
		$relation = OSMObject::fetch("relation", $_GET["relation"]);
		if($relation->getTag("ref"))
			header("Content-disposition: attachment; filename=".urlencode($relation->getTag("ref")).".gpx", true);
		elseif($relation->getTag("name"))
			header("Content-disposition: attachment; filename=".urlencode($relation->getTag("name")).".gpx", true);
	}

	echo "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n";
?>
<gpx xmlns="http://www.topografix.com/GPX/1/1" creator="OSM Route Manager" version="1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">
<!-- All data by OpenStreetMap, licensed under cc-by-sa-2.0 (http://creativecommons.org/licenses/by-sa/2.0/). -->
<?php
	if(isset($_GET["relation"]))
	{
		try
		{
?>
	<rte>
		<name><?=htmlspecialchars($relation->getTag("name"))?></name>
<?php
			if($relation->getTag("description"))
			{
?>
		<desc><?=htmlspecialchars($relation->getTag("description"))?></desc>
<?php
			}
?>
		<src>OpenStreetMap.org</src>
<?php
			if($relation->getTag("url"))
			{
?>
		<link href="<?=htmlspecialchars($relation->getTag("url"))?>" />
<?php
			}

			if($relation->getTag("route"))
			{
?>
		<type><?=htmlspecialchars($relation->getTag("route"))?></type>
<?php
			}

			$last_point = null;
			foreach((isset($_GET["segments"]) && is_array($_GET["segments"]) ? $_GET["segments"] : array_keys($segments[1])) as $k=>$segment)
			{
				if(!isset($segments[1][$segment]))
					continue;
				$r = isset($_GET["segments_rev"]) && is_array($_GET["segments_rev"]) && isset($_GET["segments_rev"][$k]) && $_GET["segments_rev"][$k];
				$s = &$segments[1][$segment];
				for($i=($r ? count($s)-1 : 0); ($r ? $i >= 0 : $i < count($s)); ($r ? $i-- : $i++))
				{
					if($last_point)
					{
						if($last_point == $s[$i])
						{
							$last_point = null;
							continue;
						}
						$last_point = null;
					}
?>
		<rtept lat="<?=htmlspecialchars($s[$i][0])?>" lon="<?=htmlspecialchars($s[$i][1])?>" />
<?php
				}
				$last_point = $s[$r ? 0 : count($s)-1];
			}
?>
	</rte>
<?php
		}
		catch(Exception $e)
		{
		}
	}
?>
</gpx>