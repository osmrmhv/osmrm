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

package de.cdauth.osm.routemanager;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

public class Cache
{
	private Connection m_connection = null;
	
	public Cache(String a_filename) throws ClassNotFoundException, SQLException
	{
		Class.forName("org.sqlite.JDBC");
		m_connection = DriverManager.getConnection("jdbc:sqlite:"+a_filename);
	}
}