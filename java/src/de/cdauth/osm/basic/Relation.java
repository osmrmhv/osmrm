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
import java.util.Vector;
import javax.xml.parsers.ParserConfigurationException;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;

public class Relation extends de.cdauth.osm.basic.Object
{
	static public final String TYPE = "relation";
	static private Hashtable<String,Relation> sm_cache = new Hashtable<String,Relation>();

	protected Relation(Element a_dom)
	{
		super(a_dom);
	}
	
	public static Hashtable<String,Relation> fetch(String[] a_ids) throws IOException, APIError, SAXException, ParserConfigurationException
	{
		return fetchWithCache(a_ids, sm_cache);
	}
	
	public static Relation fetch(String a_id) throws IOException, APIError, SAXException, ParserConfigurationException
	{
		return fetchWithCache(a_id, sm_cache);
	}
	
	protected static boolean isCached(String a_id)
	{
		return sm_cache.containsKey(a_id);
	}
	
	public static void cache(Relation a_object)
	{
		sm_cache.put(a_object.getDOM().getAttribute("id"), a_object);
	}
	
	public RelationMember[] getMembers()
	{
		NodeList members = getDOM().getElementsByTagName("member");
		RelationMember[] ret = new RelationMember[members.getLength()];
		for(int i=0; i<members.getLength(); i++)
			ret[i] = new RelationMember((Element) members.item(i));
		return ret;
	}
	
	/**
	 * Ensures that all members of the relation are downloaded and cached. This saves a lot of time when accessing them with fetch(), as fetch() makes an API call for each uncached item whereas this method can download all members at once.
	 * @param a_id
	 * @throws ParserConfigurationException 
	 * @throws SAXException 
	 * @throws APIError 
	 * @throws IOException 
	 */
	
	public static void downloadFull(String a_id) throws IOException, APIError, SAXException, ParserConfigurationException
	{
		boolean downloadNecessary = true;
		if(isCached(a_id))
		{
			downloadNecessary = false;
			for(RelationMember it : Relation.fetch(a_id).getMembers())
			{
				String type = it.getDOM().getAttribute("type");
				String id = it.getDOM().getAttribute("ref");
				boolean isCached = true;
				if(type.equals("node"))
					isCached = Node.isCached(id);
				else if(type.equals("way"))
					isCached = Way.isCached(id);
				else if(type.equals("relation"))
					isCached = Relation.isCached(id);
				if(!isCached)
				{
					downloadNecessary = true;
					break;
				}
			}
		}
		
		if(downloadNecessary)
			API.get("/relation/"+a_id+"/full");
	}
	
	private Vector<Way> getWaysRecursive(Vector<String> a_ignore_relations) throws IOException, APIError, SAXException, ParserConfigurationException
	{
		a_ignore_relations.add(this.getDOM().getAttribute("id"));
		downloadFull(this.getDOM().getAttribute("id"));

		Vector<Way> ret = new Vector<Way>();
		for(RelationMember it : getMembers())
		{
			String type = it.getDOM().getAttribute("type");
			if(type.equals("way"))
				ret.add(Way.fetch(it.getDOM().getAttribute("ref")));
			else if(type.equals("relation") && !a_ignore_relations.contains(it.getDOM().getAttribute("ref")))
				ret.addAll(Relation.fetch(it.getDOM().getAttribute("ref")).getWaysRecursive(a_ignore_relations));
		}
		return ret;
	}
	
	public Way[] getWaysRecursive() throws IOException, APIError, SAXException, ParserConfigurationException
	{
		return getWaysRecursive(new Vector<String>()).toArray(new Way[0]);
	}
	
	/**
	 * Makes segments out of the ways of this relation and its sub-relations. The return value is an array of segments. The segments consist of a list of coordinates that are connected via ways. The relation structure is irrelevant for the segmentation; a segment ends where a way is connected to two or more ways where it is not connected to any way. 
	 * The segments are sorted from north to south or from west to east (depending in which direction the two most distant ends have the greater distance from each other). The sort algorithm sorts using the smaller distance of the two ends of a segment to the northern/western one of the the two most distant points.
	 * @throws IOException
	 * @throws APIError
	 * @throws SAXException
	 * @throws ParserConfigurationException
	 */
	
	public RelationSegment[] segmentate() throws IOException, APIError, SAXException, ParserConfigurationException
	{
		ArrayList<Way> waysList = new ArrayList<Way>(Arrays.asList(getWaysRecursive()));
		
		// Make roundabout resolution table
		Hashtable<LonLat,LonLat> roundaboutReplacement = new Hashtable<LonLat,LonLat>();
		for(int i=0; i<waysList.size(); i++)
		{
			LonLat roundaboutCentre = waysList.get(i).getRoundaboutCentre();
			Node[] nodes = waysList.get(i).getMemberNodes();
			if(nodes.length <= 1)
				waysList.remove(i--);
			else if(roundaboutCentre != null)
			{
				for(Node it : nodes)
					roundaboutReplacement.put(it.getLonLat(), roundaboutCentre);
				waysList.remove(i--);
			}
		}
		
		Way[] ways = waysList.toArray(new Way[0]);
		waysList = null;
		
		// Get the first and last node of the ways
		LonLat[] waysEnds1 = new LonLat[ways.length];
		LonLat[] waysEnds2 = new LonLat[ways.length];
		LonLat i_lonlat;
		for(int i=0; i<ways.length; i++)
		{
			Node[] nodes = ways[i].getMemberNodes();
			i_lonlat = nodes[0].getLonLat();
			if(roundaboutReplacement.containsKey(i_lonlat))
				waysEnds1[i] = roundaboutReplacement.get(i_lonlat);
			else
				waysEnds1[i] = i_lonlat;
			i_lonlat = nodes[nodes.length-1].getLonLat();
			if(roundaboutReplacement.containsKey(i_lonlat))
				waysEnds2[i] = roundaboutReplacement.get(i_lonlat);
			else
				waysEnds2[i] = i_lonlat;
		}
		i_lonlat = null;
		
		// Look which ways are connected
		ArrayList<Integer> endsIndexes = new ArrayList<Integer>(); // Contains the indexes of all ways that are on one end of a segment (thus connected to more or less than 1 other way)
		Vector<Integer>[] waysConnections1 = new Vector[ways.length];
		Vector<Integer>[] waysConnections2 = new Vector[ways.length];
		for(int i=0; i<ways.length; i++)
		{
			waysConnections1[i] = new Vector<Integer>();
			waysConnections2[i] = new Vector<Integer>();
		}
		
		for(int i=0; i<ways.length; i++)
		{
			for(int j=i+1; j<ways.length; j++)
			{
				if(waysEnds1[i].equals(waysEnds1[j]))
				{
					waysConnections1[i].add(j);
					waysConnections1[j].add(i);
				}
				else if(waysEnds1[i].equals(waysEnds2[j]))
				{
					waysConnections1[i].add(j);
					waysConnections2[j].add(i);
				}
				else if(waysEnds2[i].equals(waysEnds1[j]))
				{
					waysConnections2[i].add(j);
					waysConnections1[j].add(i);
				}
				else if(waysEnds2[i].equals(waysEnds2[j]))
				{
					waysConnections2[i].add(j);
					waysConnections2[j].add(i);
				}
			}
			if(waysConnections1[i].size() != 1 || waysConnections2[i].size() != 1)
				endsIndexes.add(i);
		}
		
		waysEnds1 = null;
		waysEnds2 = null;
		
		Vector<Vector<Way>> segmentsWaysV = new Vector<Vector<Way>>();
		
		// Connect the ways and first create segments of ways (maybe later tags of the ways could be useful)
		for(int i=0; i<endsIndexes.size(); i++)
		{
			if(endsIndexes.get(i) == null)
				continue;
			int it = endsIndexes.get(i);
			int prevIt;

			Vector<Way> segment = new Vector<Way>();
			
			segment.add(ways[it]);
			Vector<Integer>[] connectionArray = (waysConnections1[it].size() != 1 ? waysConnections2 : waysConnections1);
			while(connectionArray[it].size() == 1)
			{
				prevIt = it;
				it = connectionArray[it].get(0);
				segment.add(ways[it]);
				connectionArray = (waysConnections1[it].size() > 0 && waysConnections1[it].get(0) == prevIt ? waysConnections2 : waysConnections1);
			}
			
			segmentsWaysV.add(segment);
			
			int indexOf = endsIndexes.indexOf(new Integer(it));
			if(indexOf > -1)
				endsIndexes.set(indexOf, null);
		}
		
		waysConnections1 = null;
		waysConnections2 = null;
		endsIndexes = null;
		
		Vector<Way>[] segmentsWays = segmentsWaysV.toArray(new Vector[0]);
		
		// Resolve segments into points
		Vector<Vector<LonLat>> segmentsNodesV = new Vector<Vector<LonLat>>();
		for(int i=0; i<segmentsWays.length; i++)
		{
			Vector<Way> segmentWays = segmentsWays[i];
			Vector<LonLat> segmentNodes = new Vector<LonLat>();
			Node lastEndNode = null;
			for(int j=0; j<segmentWays.size(); j++)
			{
				Node[] nodes = segmentWays.get(j).getMemberNodes();
				if(lastEndNode == null && roundaboutReplacement.containsKey(nodes[0].getLonLat()))
					segmentNodes.add(roundaboutReplacement.get(nodes[0].getLonLat()));
				boolean reverse = (lastEndNode != null && nodes[nodes.length-1].equals(lastEndNode));
				int k;
				for(k = (reverse ? nodes.length-1 : 0) + (lastEndNode == null ? 0 : (reverse ? -1 : 1)); (reverse) ? (k > 0) : (k < nodes.length); k += (reverse ? -1 : 1))
					segmentNodes.add(nodes[k].getLonLat());
				lastEndNode = nodes[k];
				if(roundaboutReplacement.containsKey(lastEndNode.getLonLat()))
					segmentNodes.add(roundaboutReplacement.get(lastEndNode.getLonLat()));
			}
			segmentsNodesV.add(segmentNodes);
		}
		
		segmentsWays = null;
		RelationSegment[] segmentsNodes = new RelationSegment[segmentsNodesV.size()];
		for(int i=0; i<segmentsNodesV.size(); i++)
			segmentsNodes[i] = new RelationSegment(segmentsNodesV.get(i).toArray(new LonLat[0]));
		segmentsNodesV = null;
		
		// Sort segments
		// Calculate the distance between all segment ends that aren’t connected to any other segment and find the greatest distance
		Vector<LonLat> endsCoordinatesV = new Vector<LonLat>();
		
		for(int i=0; i<segmentsNodes.length; i++)
		{
			boolean end1 = true;
			boolean end2 = true;
			for(int j=0; j<segmentsNodes.length; j++)
			{
				if(i == j) continue;
				
				if(end1 && (segmentsNodes[i].getEnd1().equals(segmentsNodes[j].getEnd1()) || segmentsNodes[i].getEnd1().equals(segmentsNodes[j].getEnd2())))
					end1 = false;
				if(end2 && (segmentsNodes[i].getEnd2().equals(segmentsNodes[j].getEnd1()) || segmentsNodes[i].getEnd2().equals(segmentsNodes[j].getEnd2())))
					end2 = false;
				if(end1 && end2)
					break;
			}
			if(end1)
				endsCoordinatesV.add(segmentsNodes[i].getEnd1());
			if(end2)
				endsCoordinatesV.add(segmentsNodes[i].getEnd2());
		}
		
		LonLat[] endsCoordinates = endsCoordinatesV.toArray(new LonLat[0]);
		endsCoordinatesV = null;
		LonLat[] greatestDistancePoints = new LonLat[2];
		double greatestDistance = -1;
		for(int i=0; i<endsCoordinates.length; i++)
		{
			for(int j=i+1; j<endsCoordinates.length; j++)
			{
				double distance = endsCoordinates[i].getDistance(endsCoordinates[j]);
				if(distance > greatestDistance)
				{
					greatestDistance = distance;
					greatestDistancePoints[0] = endsCoordinates[i];
					greatestDistancePoints[1] = endsCoordinates[j];
				}
			}
		}
		
		if(greatestDistance != -1)
		{
			double distanceLat = Math.abs(greatestDistancePoints[0].getLat()-greatestDistancePoints[1].getLat());
			double distanceLon = Math.abs(greatestDistancePoints[0].getLon()-greatestDistancePoints[1].getLon());
			LonLat referencePoint;
			if(distanceLat > distanceLon)
				referencePoint = greatestDistancePoints[greatestDistancePoints[0].getLat() < greatestDistancePoints[1].getLat() ? 0 : 1];
			else
				referencePoint = greatestDistancePoints[greatestDistancePoints[0].getLon() < greatestDistancePoints[1].getLon() ? 0 : 1];
			
			synchronized(RelationSegment.sm_sortingReference)
			{
				RelationSegment.sm_sortingReference = referencePoint;
				Arrays.sort(segmentsNodes);
				RelationSegment.sm_sortingReference = null;
			}
		}
		
		return segmentsNodes;
	}
}