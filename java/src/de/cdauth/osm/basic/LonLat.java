

package de.cdauth.osm.basic;

public class LonLat
{
	private double m_lon;
	private double m_lat;
	
	private static final short EARTH_RADIUS = 6367;
	
	public LonLat(double a_lon, double a_lat)
	{
		m_lon = a_lon;
		m_lat = a_lat;
	}
	
	public double getLon()
	{
		return m_lon;
	}
	
	public double getLat()
	{
		return m_lat;
	}
	
	public boolean equals(LonLat a_other)
	{
		return (getLon() == a_other.getLon() && getLat() == a_other.getLat());
	}
	
	/**
	 * Calculate the distance from this point to another.
	 * Formula from {@link http://mathforum.org/library/drmath/view/51879.html}.
	 * @param a_to
	 * @return
	 */
	
	public double getDistance(LonLat a_to)
	{
		double lat1 = getLat()*Math.PI/180;
		double lat2 = a_to.getLat()*Math.PI/180;
		double lon1 = getLon()*Math.PI/180;
		double lon2 = a_to.getLon()*Math.PI/180;

		double dlon = lon2 - lon1;
		double dlat = lat2 - lat1;
		double a = Math.pow((Math.sin(dlat/2)),2) + Math.cos(lat1) * Math.cos(lat2) * Math.pow((Math.sin(dlon/2)),2);
		return EARTH_RADIUS * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
	}
}
