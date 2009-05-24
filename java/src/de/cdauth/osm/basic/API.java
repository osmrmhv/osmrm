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
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.util.Vector;

import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;

import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;


public class API
{
	private static final String API_SERVER = "www.openstreetmap.org";
	private static final int API_PORT = 80;
	private static final String API_PREFIX = "/api/0.6";

	public static Object[] get(String a_url, String a_server, int a_port, String a_prefix) throws IOException, SAXException, ParserConfigurationException, APIError
	{
		HttpURLConnection connection = (HttpURLConnection) new URL("http://"+a_server+":"+a_port+a_prefix+a_url).openConnection();
		connection.setRequestMethod("GET");
		connection.setRequestProperty("User-Agent", "OSM Route Manager");
		System.out.println("API call "+connection.getURL().toString());
		connection.connect();

		if(connection.getResponseCode() != 200)
			throw new APIError(connection);

		Document dom = DocumentBuilderFactory.newInstance().newDocumentBuilder().parse(connection.getInputStream());
		Element root = null;
		NodeList nodes = dom.getChildNodes();
		for(int i=0; i<nodes.getLength(); i++)
		{
			if(nodes.item(i).getNodeType() != org.w3c.dom.Node.ELEMENT_NODE)
				continue;
			root = (Element) nodes.item(i);
			break;
		}

		if(root == null)
			throw new APIError("The API server sent no data.");

		Vector<de.cdauth.osm.basic.Object> ret = new Vector<de.cdauth.osm.basic.Object>();

		nodes = root.getChildNodes();
		for(int i=0; i<nodes.getLength(); i++)
		{
			if(nodes.item(i).getNodeType() != org.w3c.dom.Node.ELEMENT_NODE)
				continue;
			de.cdauth.osm.basic.Object el = (de.cdauth.osm.basic.Object) nodes.item(i);
			ret.add(el);
			de.cdauth.osm.basic.Object.cache(el);
		}

		return ret.toArray(new de.cdauth.osm.basic.Object[0]);
	}

	public static de.cdauth.osm.basic.Object[] get(String a_url) throws IOException, APIError, SAXException, ParserConfigurationException
	{
		return get(a_url, API_SERVER, API_PORT, API_PREFIX);
	}

	public static String joinStringArray(String a_delim, String[] a_array)
	{
		StringBuffer ret = new StringBuffer();
		boolean first = true;
		for(int i=0; i<a_array.length; i++)
		{
			if(a_array[i] == null)
				continue;
			if(first)
				first = false;
			else
				ret.append(a_delim);
			ret.append(a_array[i]);
		}
		return ret.toString();
	}
}