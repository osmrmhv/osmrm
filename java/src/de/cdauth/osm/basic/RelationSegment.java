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

public class RelationSegment implements Comparable<RelationSegment>
{
	private LonLat[] m_nodes;
	private double m_distance = -1;
	
	/**
	 * Segments are sorted by their distance to this point. Be sure to set this value before using compareTo() and to unset it afterwards, both inside a synchronized block.
	 */
	protected static LonLat sm_sortingReference = null;
	protected static java.lang.Object sm_sortingReferenceSynchronized = new java.lang.Object();
	
	protected RelationSegment(LonLat[] a_nodes)
	{
		if(a_nodes.length < 2)
			throw new IllegalArgumentException("A segment has to consist of minimum two nodes.");
		m_nodes = a_nodes;
	}
	
	private Double getReferenceDistance()
	{
		return new Double(Math.min(m_nodes[0].getDistance(sm_sortingReference), m_nodes[m_nodes.length-1].getDistance(sm_sortingReference)));
	}
	
	/**
	 * Only works when sm_sortingReference is set, for internal use only. 
	 */
	
	public int compareTo(RelationSegment o)
	{
		return getReferenceDistance().compareTo(o.getReferenceDistance());
	}
	
	public LonLat getEnd1()
	{
		return m_nodes[0];
	}
	
	public LonLat getEnd2()
	{
		return m_nodes[m_nodes.length-1];
	}
	
	public LonLat[] getNodes()
	{
		return m_nodes;
	}
	
	public double getDistance()
	{
		if(m_distance == -1)
		{
			m_distance = 0;
			LonLat lastNode = m_nodes[0];
			for(int i=1; i<m_nodes.length; i++)
			{
				m_distance += lastNode.getDistance(m_nodes[i]);
				lastNode = m_nodes[i];
			}
		}
		return m_distance;
	}
}
