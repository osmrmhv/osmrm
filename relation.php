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

	require_once("include.php");

	if(isset($_GET["id"]) && trim($_GET["id"]) != "")
		$GUI->option("title", sprintf(_("Relation %s"), $_GET["id"]));

	$GUI->head();

	if(!isset($_GET["id"]))
	{
		header("Location: http://".$_SERVER["HTTP_HOST"].dirname($_SERVER["PHP_SELF"]), true, 307);
		die();
	}

	$segments = OSMRelation::segmentate($_GET["id"], isset($_GET["refresh"]) && $_GET["refresh"]);
	$relation = OSMObject::fetch("relation", $_GET["id"]);

	$total_length = 0;

	// Calculate segment connections and lengthes
	$segments_connections = array_pad(array(), count($segments[1]), array(0, array(), array(), array(), array()));
	for($k1=0; $k1 < count($segments[1]); $k1++)
	{
		$segment1 = &$segments[1][$k1];

		$point = &$segment1[0];
		for($j=1; $j<count($segment1); $j++)
		{
			$segments_connections[$k1][0] += getDistance($point, $segment1[$j]);
			$point = &$segment1[$j];
		}
		$total_length += $segments_connections[$k1][0];

		$first_node = $segment1[0];
		$last_node = $segment1[count($segment1)-1];
		for($k2=$k1+1; $k2 < count($segments[1]); $k2++)
		{
			$segment2 = &$segments[1][$k2];

			$that_first_node = $segment2[0];
			$that_last_node = $segment2[count($segment2)-1];

			if($first_node == $that_first_node)
			{
				$segments_connections[$k1][1][] = $k2;
				$segments_connections[$k2][1][] = $k1;
			}
			elseif($first_node == $that_last_node)
			{
				$segments_connections[$k1][1][] = $k2;
				$segments_connections[$k2][2][] = $k1;
			}
			elseif($last_node == $that_first_node)
			{
				$segments_connections[$k1][2][] = $k2;
				$segments_connections[$k2][1][] = $k1;
			}
			elseif($last_node == $that_last_node)
			{
				$segments_connections[$k1][2][] = $k2;
				$segments_connections[$k2][2][] = $k2;
			}

			$distance1 = getDistance($first_node, $that_first_node);
			$distance2 = getDistance($last_node, $that_first_node);
			$distance3 = getDistance($first_node, $that_last_node);
			$distance4 = getDistance($last_node, $that_last_node);

			$segments_connections[$k1][3][$k2] = min($distance1, $distance3);
			$segments_connections[$k1][4][$k2] = min($distance2, $distance4);
			$segments_connections[$k2][3][$k1] = min($distance1, $distance2);
			$segments_connections[$k2][4][$k1] = min($distance3, $distance4);
		}
	}
?>
<ul>
	<li><a href="./"><?=htmlspecialchars(_("Back to home page"))?></a></li>
	<li><a href="http://betaplace.emaitie.de/webapps.relation-analyzer/analyze.jsp?relationId=<?=htmlspecialchars(urlencode($_GET["id"]))?>"><?=htmlspecialchars(_("Open this in OSM Relation Analyzer"))?></a></li>
	<li><a href="http://www.openstreetmap.org/browse/relation/<?=htmlspecialchars(urlencode($_GET["id"]))?>"><?=htmlspecialchars(_("Browse on OpenStreetMap"))?></a></li>
</ul>
<noscript><p><strong><?=htmlspecialchars(_("Note that many features of this page will not work without JavaScript."))?></strong></p></noscript>
<p><?=sprintf(htmlspecialchars(_("The data was last refreshed on %s. The timestamp of the relation is %s. If you think one of the members might have been changed, %sreload the data manually%s.")), gmdate("Y-m-d\\TH:i:s\\Z", $segments[0]), $relation->getDOM()->getAttribute("timestamp"), "<a href=\"?id=".htmlspecialchars(urlencode($_GET["id"])."&refresh=1")."\">", "</a>")?></p>
<h2><?=htmlspecialchars(_("Tags"))?></h2>
<dl>
<?php
	$tags = $relation->getDOM()->getElementsByTagName("tag");
	for($i=0; $i<$tags->length; $i++)
	{
		$tag = $tags->item($i);
?>
	<dt><?=htmlspecialchars($tag->getAttribute("k"))?></dt>
	<dd><?=htmlspecialchars($tag->getAttribute("v"))?></dd>
<?php
	}
?>
</dl>
<h2><?=htmlspecialchars(_("Details"))?></h2>
<dl>
	<dt><?=htmlspecialchars(_("Last changed"))?></dt>
	<dd><?=sprintf(_("%s by %s"), $relation->getDOM()->getAttribute("timestamp"), "<a href=\"http://www.openstreetmap.org/user/".rawurlencode($relation->getDOM()->getAttribute("user"))."\">".htmlspecialchars($relation->getDOM()->getAttribute("user"))."</a>")?></dd>

	<dt><?=htmlspecialchars(_("Total length"))?></dt>
	<dd><?=str_replace("\n", "&thinsp;", number_format($total_length, 2, ",", "\n"))?>&thinsp;km</dd>

	<dt><?=htmlspecialchars(_("Sub-relations"))?></dt>
	<dd><ul>
<?php
	$sub_relations = array();
	$members = $relation->getDOM()->getElementsByTagName("member");
	for($i=0; $i<$members->length; $i++)
	{
		$member = $members->item($i);
		if($member->getAttribute("type") == "relation")
			$sub_relations[] = $member->getAttribute("ref");
	}
	$sub_relations = OSMObject::fetch("relation", $sub_relations);
	foreach($sub_relations as $k=>$sub_relation)
	{
?>
		<li><a href="?id=<?=htmlspecialchars(urlencode($k))?>"><?=htmlspecialchars($k)?> (<?=htmlspecialchars($sub_relation->getTag("name"))?>)</a></li>
<?php
	}
?>
	</ul></dd>

	<dt><?=htmlspecialchars(_("Parent relations"))?></dt>
	<dd><ul>
<?php
	$parent_relations = OSMAPI::get("/relation/".$_GET["id"]."/relations");
	foreach($parent_relations as $parent_relation)
	{
		$k = $parent_relation->getDOM()->getAttribute("id");
?>
		<li><a href="?id=<?=htmlspecialchars(urlencode($k))?>"><?=htmlspecialchars($k)?> (<?=htmlspecialchars($parent_relation->getTag("name"))?>)</a></li>
<?php
	}
?>
	</ul></dd>
</dl>
<h2><?=htmlspecialchars(_("Segments"))?></h2>
<p><?=htmlspecialchars(_("Get GPS coordinates by clicking on the map."))?></p>
<div id="segment-list">
	<table>
		<thead>
			<tr>
				<th><?=htmlspecialchars(_("Segment #"))?></th>
				<th><?=htmlspecialchars(_("Length"))?></th>
				<th><?=htmlspecialchars(_("Distance to next segments"))?></th>
				<th><?=htmlspecialchars(_("Visible"))?></th>
				<th><?=htmlspecialchars(_("Zoom"))?></th>
				<th><?=htmlspecialchars(_("Add to personal route"))?></th>
			</tr>
		</thead>
		<tbody>
<?php
	foreach($segments[1] as $i=>$segment)
	{
?>
			<tr onmouseover="highlightSegment(<?=$i?>);" onmouseout="unhighlightSegment(<?=$i?>);" id="tr-segment-<?=$i?>" class="tr-segment-normal">
				<td><?=htmlspecialchars($i+1)?></td>
				<td><?=str_replace("\n", "&thinsp;", number_format($segments_connections[$i][0], 2, ",", "\n"))?>&thinsp;km</td>
				<td><?php if(count($segments[1]) > 1){?><?=str_replace("\n", "&thinsp;", number_format(min($segments_connections[$i][3]), 2, ",", "\n"))?>&thinsp;km, <?=str_replace("\n", "&thinsp;", number_format(min($segments_connections[$i][4]), 2, ",", "\n"))?>&thinsp;km<?php }?></td>
				<td><input type="checkbox" name="select-segment[<?=$i?>]" id="select-segment-<?=$i?>" checked="checked" onclick="refreshSelected()" /></td>
				<td><a href="javascript:zoomToSegment(<?=$i?>);"><?=htmlspecialchars(_("Zoom"))?></a></td>
				<td><button disabled="disabled" id="pr-button-<?=$i?>" onclick="if(this.osmroutemanager) addPRSegment(this.osmroutemanager.i, this.osmroutemanager.reversed);">+</button></td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
	<h3><?=htmlspecialchars(_("Personal route"))?></h3>
	<ol id="personal-route"></ol>
	<ul class="buttons" id="personal-route-buttons" style="display:none;">
		<li><button id="personal-route-pop" onclick="PRPop()">&minus;</button></li>
		<li><button onclick="PRDownloadGPX()"><?=htmlspecialchars(_("Download GPX"))?></button></li>
	</ul>
</div>
<div id="map"></div>
<script type="text/javascript">
// <![CDATA[
	var map = new OpenLayers.Map ("map", {
		controls:[
			new OpenLayers.Control.Navigation(),
			new OpenLayers.Control.PanZoomBar(),
			new OpenLayers.Control.LayerSwitcher(),
			new OpenLayers.Control.Attribution()],
		maxExtent: new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34),
		maxResolution: 156543.0399,
		numZoomLevels: 19,
		units: 'm',
		projection: new OpenLayers.Projection("EPSG:900913"),
		displayProjection: new OpenLayers.Projection("EPSG:4326")
	} );

	window.onresize = function(){ document.getElementById("map").style.height = Math.round(window.innerHeight*.8)+"px"; map.updateSize(); }
	window.onresize();

	layerMapnik = new OpenLayers.Layer.OSM.Mapnik("Mapnik");
	map.addLayer(layerMapnik);
	layerTilesAtHome = new OpenLayers.Layer.OSM.Osmarender("Osmarender");
	map.addLayer(layerTilesAtHome);
	layerCycleMap = new OpenLayers.Layer.OSM.CycleMap("CycleMap");
	map.addLayer(layerCycleMap);

	var styleMapNormal = new OpenLayers.StyleMap({strokeColor: "#0000ff", strokeWidth: 3, strokeOpacity: 0.5});
	var styleMapHighlight = new OpenLayers.StyleMap({strokeColor: "#ff0080", strokeWidth: 3, strokeOpacity: 0.5});
	var segments = [ ];
	var segments_highlighted = [ ];
	var segments_are_highlighted = [ ];
	var segments_data = [ ];
	var projection = new OpenLayers.Projection("EPSG:4326");
<?php
	foreach($segments[1] as $i=>$segment)
	{
		$segment_code = array();
		foreach($segment as $point)
			$segment_code[] = "new OpenLayers.Feature(segments[".$i."], new OpenLayers.LonLat(".$point[1].", ".$point[0].").transform(projection, map.getProjectionObject()))";
?>
	segments[<?=$i?>] = new OpenLayers.Layer.PointTrack(<?=jsescape(sprintf(_("Segment %s"), $i+1))?>, {
		styleMap: styleMapNormal,
		projection: new OpenLayers.Projection("EPSG:4326"),
		displayInLayerSwitcher: false
	});
	segments_data[<?=$i?>] = [<?=implode(",", $segment_code)?>];
	segments[<?=$i?>].addNodes(segments_data[<?=$i?>]);
<?php
	}
?>
	map.addLayers(segments);
	layerMarkers = new OpenLayers.Layer.Markers("Markers", { displayInLayerSwitcher: false });
	map.addLayer(layerMarkers);

	var extent;
	if(segments.length > 0)
	{
		extent = segments[0].getDataExtent();
		for(var i=1; i<segments.length; i++)
			extent.extend(segments[i].getDataExtent());
	}
	if(extent)
		map.zoomToExtent(extent);
	else
		map.zoomToMaxExtent();

	OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
		defaultHandlerOptions: {
			'single': true,
			'double': false,
			'pixelTolerance': 0,
			'stopSingle': false,
			'stopDouble': false
		},

		initialize: function(options) {
			this.handlerOptions = OpenLayers.Util.extend(
				{}, this.defaultHandlerOptions
			);
			OpenLayers.Control.prototype.initialize.apply(
				this, arguments
			);
			this.handler = new OpenLayers.Handler.Click(
				this, {
					'click': this.trigger
				}, this.handlerOptions
			);
		},

		trigger: function(e) {
			var lonlat = map.getLonLatFromViewPortPx(e.xy);
			lonlat_readable = lonlat.clone().transform(map.getProjectionObject(), projection);
			var icon = new OpenLayers.Icon('http://www.openstreetmap.org/openlayers/img/marker.png', new OpenLayers.Size(21,25), new OpenLayers.Pixel(-9, -25));
			var marker = new OpenLayers.Marker(lonlat,icon);
			layerMarkers.addMarker(marker);
			var framecloud = new OpenLayers.Popup.FramedCloud("lonlat", lonlat, null, "<dl><dt><?=htmlspecialchars(_("Longitude"))?></dt><dd>"+Math.round(lonlat_readable.lon*100000000)/100000000+"</dd><dt><?=htmlspecialchars(_("Latitude"))?></dt><dd>"+Math.round(lonlat_readable.lat*100000000)/100000000+"</dd></dl><ul><li><a href=\"http://www.openstreetmap.org/?lat="+lonlat_readable.lat+"&amp;lon="+lonlat_readable.lon+"&amp;zoom="+map.getZoom()+"\"><?=htmlspecialchars(_("OpenStreetMap Permalink"))?></a></li><li><a href=\"http://www.openstreetmap.org/?mlat="+lonlat_readable.lat+"&amp;mlon="+lonlat_readable.lon+"&amp;zoom="+map.getZoom()+"\"><?=htmlspecialchars(_("OpenStreetMap Marker"))?></a></li><li><a href=\"http://maps.google.com/?q="+lonlat_readable.lat+","+lonlat_readable.lon+"\"><?=htmlspecialchars(_("Google Maps Marker"))?></a></li><li><a href=\"http://maps.yahoo.com/broadband/#lat="+lonlat_readable.lat+"&amp;lon="+lonlat_readable.lon+"&amp;zoom="+map.getZoom()+"\"><?=htmlspecialchars(_("Yahoo Maps Permalink"))?></a></li></ul>", icon, true, function(){layerMarkers.removeMarker(marker); framecloud.destroy(); });
			map.addPopup(framecloud);
		}
	});

	var click = new OpenLayers.Control.Click();
	map.addControl(click);
	click.activate();

<?php
	$pr_allowed_forward = array();
	$pr_allowed_backward = array();
	foreach($segments_connections as $i=>$segment)
	{
		$pr_allowed_forward[] = "[ ".implode(", ", $segment[2])." ]";
		$pr_allowed_backward[] = "[ ".implode(", ", $segment[1])." ]";
	}
?>
	var pr_allowed = [ [ <?=implode(", ", $pr_allowed_forward)?> ], [ <?=implode(", ", $pr_allowed_backward)?> ] ];

	var pr_stack = [ ];

	function addPRSegment(i, reversed)
	{
		pr_stack.push([i, reversed ? 1 : 0]);

		var li = document.createElement("li");
		li.id = "pr-stack-"+(pr_stack.length-1);
		li.onmouseover = function(){highlightSegment(i);}
		li.onmouseout = function(){unhighlightSegment(i);}
		li.appendChild(document.createTextNode((pr_stack.length >= 2 && pr_stack[pr_stack.length-2][0] == i) ? "<?=sprintf(_("Segment %s (reversed)"), "\"+(i+1)+\"")?>" : "<?=sprintf(_("Segment %s"), "\"+(i+1)+\"")?>"));
		document.getElementById("personal-route").appendChild(li);

		updatePRButtons();
	}

	function PRPop()
	{
		if(pr_stack.length > 0)
		{
			pr_stack.pop();
			document.getElementById("personal-route").removeChild(document.getElementById("pr-stack-"+(pr_stack.length)));
			updatePRButtons();
		}
	}

	function PRDownloadGPX()
	{
		var segments = [ ];
		var segments_rev = [ ];

		for(var i=0; i<pr_stack.length; i++)
		{
			segments[i] = "segments[]="+encodeURIComponent(pr_stack[i][0]);
			segments_rev[i] = "segments_rev[]="+(pr_stack[i][1] ? "1" : "0");
		}

		location.href = "gpx.php?relation=<?=urlencode($_GET["id"])?>&"+segments.join("&")+segments_rev.join("&");
	}

	function updatePRButtons()
	{
		document.getElementById("personal-route-buttons").style.display = pr_stack.length > 0 ? "" : "none";

		last = pr_stack.length > 0 ? pr_stack[pr_stack.length-1] : null;
		for(var i=0; i<segments.length; i++)
		{
			var allowed,reversed;
			if(last && i == last[0])
			{
				allowed = true;
				reversed = !last[1];
			}
			else if(!last || pr_allowed[last[1]][last[0]].length < 1)
			{
				if(pr_allowed[0][i].length < 1)
				{
					allowed = true;
					reversed = true;
				}
				else if(pr_allowed[1][i].length < 1)
				{
					allowed = true;
					reversed = false;
				}
				else
					allowed = false;
			}
			else
			{
				allowed = false;
				for(var j=0; j<pr_allowed[0][i].length; j++)
				{
					if(pr_allowed[0][i][j] == last[0])
					{
						allowed = true;
						reversed = true;
						break;
					}
				}
				if(!allowed)
				{
					for(var j=0; j<pr_allowed[1][i].length; j++)
					{
						if(pr_allowed[1][i][j] == last[0])
						{
							allowed = true;
							reversed = false;
							break;
						}
					}
				}
			}

			var button = document.getElementById("pr-button-"+i);
			if(!allowed)
			{
				button.disabled = true;
				button.osmroutemanager = null;
			}
			else
			{
				button.disabled = false;
				button.osmroutemanager = { i: i, reversed: reversed };
			}
		}
	}

	updatePRButtons();

	function highlightSegment(i)
	{
		segments_are_highlighted[i] = true;
		if(!segments[i].getVisibility())
			return;

		if(!segments_highlighted[i])
		{
			segments_highlighted[i] = new OpenLayers.Layer.PointTrack(segments[i].name, {
				styleMap: styleMapHighlight,
				projection: new OpenLayers.Projection("EPSG:4326"),
				displayInLayerSwitcher: false
			});
			segments_highlighted[i].addNodes(segments_data[i]);
			segments_highlighted[i].setZIndex(100000);
			map.addLayer(segments_highlighted[i]);
		}

		segments[i].setVisibility(false);
		segments_highlighted[i].setVisibility(true);
		document.getElementById("tr-segment-"+i).className = "tr-segment-highlight";
	}

	function unhighlightSegment(i)
	{
		segments_are_highlighted[i] = false;
		document.getElementById("tr-segment-"+i).className = "tr-segment-normal";

		if(!segments_highlighted[i] || !segments_highlighted[i].getVisibility())
			return;
		segments_highlighted[i].setVisibility(false);
		segments[i].setVisibility(true);
	}

	function refreshSelected()
	{
		for(var i=0; i<segments.length; i++)
		{
			if(document.getElementById("select-segment-"+i).checked && (!segments_highlighted[i] || !segments_highlighted[i].getVisibility()) && !segments[i].getVisibility())
			{
				segments[i].setVisibility(true);
				if(segments_are_highlighted[i])
					highlightSegment(i);
			}
			else if(!document.getElementById("select-segment-"+i).checked)
			{
				segments[i].setVisibility(false);
				if(segments_highlighted[i])
					segments_highlighted[i].setVisibility(false);
			}
		}
	}

	refreshSelected();

	function zoomToSegment(i)
	{
		var extent = segments[i].getDataExtent();
		map.zoomToExtent(extent);
	}
// ]]>
</script>
<?php
	$GUI->foot();