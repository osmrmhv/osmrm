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

package de.cdauth.osm.basic;

import java.io.IOException;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Hashtable;
import java.util.Map;
import java.util.TreeMap;
import java.util.Vector;
import javax.xml.parsers.ParserConfigurationException;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;

public class ChangesetContent extends XMLObject
{
	static private Hashtable<String,ChangesetContent> sm_cache = new Hashtable<String,ChangesetContent>();
	
	public enum ChangeType { create, modify, delete };
	
	protected ChangesetContent(Element a_dom)
	{
		super(a_dom);
	}
	
	public static ChangesetContent fetch(String a_id) throws IOException, APIError, SAXException, ParserConfigurationException
	{
		if(isCached(a_id))
			return sm_cache.get(a_id);
		ChangesetContent root = new ChangesetContent(API.fetch("/changeset/"+a_id+"/download"));
		sm_cache.put(a_id, root);
		return root;
	}
	
	protected static boolean isCached(String a_id)
	{
		return sm_cache.containsKey(a_id);
	}
	
	public Object[] getMemberObjects(ChangeType a_type)
	{
		ArrayList<Object> ret = new ArrayList<Object>();
		NodeList nodes = getDOM().getElementsByTagName(a_type.toString());
		for(int i=0; i<nodes.getLength(); i++)
			ret.addAll(API.makeObjects((Element) nodes.item(i), false));
		return ret.toArray(new Relation[0]);
	}
	
	public Hashtable<Object,Object> getPreviousVersions() throws IOException, SAXException, ParserConfigurationException, APIError
	{
		Object[] newVersions = getMemberObjects(ChangeType.modify);
		Hashtable<Object,Object> ret = new Hashtable<Object,Object>();
		for(int i=0; i<newVersions.length; i++)
		{
			Object last = null;
			String tagName = newVersions[i].getDOM().getTagName();
			try
			{
				if(tagName.equals("node"))
					last = Node.fetch(newVersions[i].getDOM().getAttribute("id"), ""+(Long.parseLong(newVersions[i].getDOM().getAttribute("version"))-1));
				else if(tagName.equals("way"))
					last = Way.fetch(newVersions[i].getDOM().getAttribute("id"), ""+(Long.parseLong(newVersions[i].getDOM().getAttribute("version"))-1));
				else if(tagName.equals("relation"))
					last = Relation.fetch(newVersions[i].getDOM().getAttribute("id"), ""+(Long.parseLong(newVersions[i].getDOM().getAttribute("version"))-1));
			}
			catch(APIError e)
			{
			}
			ret.put(newVersions[i], last);
		}
		return ret;
	}
	
	public void getNodeChanges()
	{
		
	}
}
