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

	$GUI->head();
?>
<form action="relation.php" method="get" id="lookup-form">
	<fieldset>
		<legend><?=htmlspecialchars(_("Lookup relation by ID"))?></legend>
		<dl>
			<dt><label for="i-lookup-id"><?=htmlspecialchars(_("Relation ID"))?></label></dt>
			<dd><input type="text" name="id" id="i-lookup-id" /></dd>
		</dl>
		<button type="submit"><?=htmlspecialchars(_("Lookup"))?></button>
	</fieldset>
</form>
<form action="#search-form" method="get" id="search-form">
	<fieldset>
		<legend><?=htmlspecialchars(_("Search for relations"))?></legend>
<?php
	if(isset($_GET["search-key"]) && isset($_GET["search-value"]) && trim($_GET["search-key"]) != "" && trim($_GET["search-value"]) != "")
	{
		try
		{
			if(isset($_GET["xapi"]))
				$results = OSMXAPI::get("/relation[".$_GET["search-key"]."=".$_GET["search-value"]."]");
			else
				$results = OSMAPI::get("/relations/search?type=".urlencode($_GET["search-key"])."&value=".urlencode($_GET["search-value"]));
?>
		<table class="result">
			<thead>
				<tr>
					<th>type</th>
					<th>route</th>
					<th>ref</th>
					<th>name</th>
					<th><?=htmlspecialchars(_("Lookup"))?></th>
				</tr>
			</thead>
			<tbody>
<?php
			foreach($results as $object)
			{
				if(!($object instanceof OSMRelation))
					continue;
?>
				<tr>
					<td><?=htmlspecialchars($object->getTag("type"))?></td>
					<td><?=htmlspecialchars($object->getTag("route"))?></td>
					<td><?=htmlspecialchars($object->getTag("ref"))?></td>
					<td><?=htmlspecialchars($object->getTag("name"))?></td>
					<td><a href="relation.php?id=<?=htmlspecialchars(urlencode($object->getDOM()->getAttribute("id")))?>"><?=htmlspecialchars(_("Lookup"))?></a></td>
				</tr>
<?php
			}
?>
			</tbody>
		</table>
<?php
		}
		catch(Exception $e)
		{
?>
		<p class="error"><?=htmlspecialchars($e->getMessage())?></p>
<?php
		}
	}
?>
		<dl>
			<dt><label for="i-search-key"><?=htmlspecialchars(_("Key"))?></label></dt>
			<dd><select id="i-search-key" name="search-key">
				<option name="name"<?=isset($_GET["search-key"]) && $_GET["search-key"] == "name" ? " selected=\"selected\"" : ""?>>name</option>
				<option name="ref"<?=isset($_GET["search-key"]) && $_GET["search-key"] == "ref" ? " selected=\"selected\"" : ""?>>ref</option>
				<option name="operator"<?=isset($_GET["search-key"]) && $_GET["search-key"] == "operator" ? " selected=\"selected\"" : ""?>>operator</option>
			</select></dd>

			<dt><label for="i-search-value"><?=htmlspecialchars(_("Value"))?></label></dt>
			<dd><input type="text" id="i-search-value" name="search-value" value="<?=isset($_GET["search-value"]) ? $_GET["search-value"] : ""?>" /></dd>
		</dl>
		<input type="submit" value="<?=htmlspecialchars(_("Search using OSM API"))?>" />
		<input type="submit" name="xapi" value="<?=htmlspecialchars(_("Search using XAPI"))?>" />
		<p><?=htmlspecialchars(_("OSM API will probably be more current and a lot faster but won’t let you use wildcards."))?></p>
	</fieldset>
</form>
<?php
	$GUI->foot();