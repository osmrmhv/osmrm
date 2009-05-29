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
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Hashtable;
import java.util.TreeMap;
import java.util.TreeSet;

import javax.xml.parsers.ParserConfigurationException;

import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;

import de.cdauth.osm.basic.API;

/**
 * Parent class for all geographical objects in OSM, currently Nodes, Ways and Relations.
*/

abstract public class Object extends XMLObject
{
	protected Object(Element a_dom)
	{
		super(a_dom);
	}
	
	public boolean equals(java.lang.Object a_other)
	{
		if(a_other instanceof Object)
		{
			Object other = (Object) a_other;
			return (getDOM().getTagName().equals(other.getDOM().getTagName()) && !getDOM().getAttribute("id").equals("") && getDOM().getAttribute("id").equals(other.getDOM().getAttribute("id")) && getDOM().getAttribute("version").equals(other.getDOM().getAttribute("version")));
		}
		else
			return false;
	}
	
	public int hashCode()
	{
		return new Long(getDOM().getAttribute("id")).hashCode();
	}
	
	protected static <T extends Object> T fetchVersion(String a_id, Hashtable<String,T> a_cache, String a_type, String a_version) throws IOException, APIError, SAXException, ParserConfigurationException
	{
		if(a_cache.containsKey(a_id))
		{
			T el = a_cache.get(a_id);
			if(el.getDOM().getAttribute("version").equals(a_version))
				return el;
		}
		
		Object[] fetched = API.get("/"+a_type+"/"+a_id+"/"+a_version);
		if(fetched.length < 1)
			throw new APIError("Server sent no data.");
		return (T) fetched[0];
	}

	/**
	 * Internal function to fetch objects of type T with the IDs a_ids if they do not already exist in a_cache.
	 * @param <T>
	 * @param a_ids
	 * @param a_cache
	 * @return
	 * @throws IOException
	 * @throws APIError
	 * @throws SAXException
	 * @throws ParserConfigurationException
	 */
	
	protected static <T extends Object> Hashtable<String,T> fetchWithCache(String[] a_ids, Hashtable<String,T> a_cache, String a_type) throws IOException, APIError, SAXException, ParserConfigurationException
	{
		Hashtable<String,T> ret = new Hashtable<String,T>();
		ArrayList<String> ids = new ArrayList<String>(Arrays.asList(a_ids));
		for(int i=0; i<ids.size(); i++)
		{
			if(!a_cache.containsKey(ids.get(i)))
				continue;
			ret.put(ids.get(i), a_cache.get(ids.get(i)));
			ids.remove(i--);
		}
		
		if(ids.size() > 0)
		{
			Object[] fetched;
			if(ids.size() == 1)
				fetched = API.get("/"+a_type+"/"+ids.get(0)); // URLEncoder.encode(, "UTF-8");
			else
				fetched = API.get("/"+a_type+"s/?"+a_type+"s="+API.joinStringArray(",", ids.toArray(new String[0])));
			for(int i=0; i<fetched.length; i++)
				ret.put(fetched[i].getDOM().getAttribute("id"), (T)fetched[i]);
		}
		
		return ret;
	}
	
	/**
	 * Returns an OSM Object; fetches it from the API if it isn’t cached already.
	 * @param a_id
	 * @param a_cache
	 * @return
	 * @throws IOException
	 * @throws APIError
	 * @throws SAXException
	 * @throws ParserConfigurationException
	 */
	
	protected static <T extends Object> T fetchWithCache(String a_id, Hashtable<String,T> a_cache, String a_type) throws IOException, APIError, SAXException, ParserConfigurationException
	{
		String[] ids = { a_id };
		return fetchWithCache(ids, a_cache, a_type).get(a_id);
	}
	
	/**
	 * Returns a TreeMap of all versions of the element. The versions are ordered from the oldest to the newest. The indexes of the TreeMap match the version number.
	 * @param <T>
	 * @param a_id
	 * @param a_type
	 * @return
	 * @throws APIError 
	 * @throws ParserConfigurationException 
	 * @throws SAXException 
	 * @throws IOException 
	 */
	protected static <T extends Object> TreeMap<Long,T> fetchHistory(String a_id, Hashtable<String,T> a_cache, String a_type) throws IOException, SAXException, ParserConfigurationException, APIError
	{
		Object[] historyElements = API.get("/"+a_type+"/history", false);
		TreeMap<Long,T> ordered = new TreeMap<Long,T>();
		for(Object element : historyElements)
			ordered.put(Long.parseLong(element.getDOM().getAttribute("version")), (T)element);
		if(a_cache != null)
		{
			T last = ordered.get(ordered.lastKey());
			a_cache.put(last.getDOM().getAttribute("id"), last);
		}
		return ordered;
	}
	
	/**
	 * Returns the value of a tag on this object. If the tag is not set, an empty string is returned. If the tag is set multiple times (should not be possible in API 0.6), the values are joined using a comma.
	 * @param a_tagname
	 * @return
	 */

	public String getTag(String a_tagname)
	{
		StringBuffer ret = new StringBuffer();
		boolean ret_first = true;
		NodeList tags = getDOM().getElementsByTagName("tag");
		for(int i=0; i<tags.getLength(); i++)
		{
			if(tags.item(i).getNodeType() != org.w3c.dom.Node.ELEMENT_NODE)
				continue;
			Element item = (Element) tags.item(i);
			if(item.getAttribute("k") != a_tagname)
				continue;
			if(ret_first)
				ret_first = false;
			else
				ret.append(",");
			ret.append(item.getAttribute("v"));
		}
		return ret.toString();
	}
}