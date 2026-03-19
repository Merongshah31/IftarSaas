import { createContext, useContext, useState, useEffect } from 'react';
import { authAPI } from '../api/client';

const AppContext = createContext();

export const AppProvider = ({ children }) => {
  const [adminUser, setAdminUser] = useState(null);
  const [masjid, setMasjid] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadSession();
  }, []);

  const clearSession = () => {
    localStorage.removeItem('admin_token');
    localStorage.removeItem('masjid_id');
    localStorage.removeItem('masjid_name');
    setAdminUser(null);
    setMasjid(null);
  };

  const loadSession = async () => {
    const token = localStorage.getItem('admin_token');

    if (!token) {
      setLoading(false);
      return;
    }

    try {
      const response = await authAPI.me();
      const user = response.data?.user;
      const masjidData = response.data?.masjid;

      setAdminUser(user || null);
      setMasjid(masjidData || null);

      if (masjidData?.id) {
        localStorage.setItem('masjid_id', String(masjidData.id));
      }
      if (masjidData?.nama_masjid) {
        localStorage.setItem('masjid_name', masjidData.nama_masjid);
      }
    } catch (err) {
      clearSession();
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const login = async (credentials) => {
    const response = await authAPI.login(credentials);
    const token = response.data?.token;
    const user = response.data?.user;
    const masjidData = response.data?.masjid;

    if (token) {
      localStorage.setItem('admin_token', token);
    }
    if (masjidData?.id) {
      localStorage.setItem('masjid_id', String(masjidData.id));
    }
    if (masjidData?.nama_masjid) {
      localStorage.setItem('masjid_name', masjidData.nama_masjid);
    }

    setAdminUser(user || null);
    setMasjid(masjidData || null);

    return response;
  };

  const register = async (payload) => {
    const response = await authAPI.register(payload);
    const token = response.data?.token;
    const user = response.data?.user;
    const masjidData = response.data?.masjid;

    if (token) {
      localStorage.setItem('admin_token', token);
    }
    if (masjidData?.id) {
      localStorage.setItem('masjid_id', String(masjidData.id));
    }
    if (masjidData?.nama_masjid) {
      localStorage.setItem('masjid_name', masjidData.nama_masjid);
    }

    setAdminUser(user || null);
    setMasjid(masjidData || null);

    return response;
  };

  const logout = async () => {
    try {
      await authAPI.logout();
    } catch {
      // Session may already be invalid; continue clearing local state.
    }

    clearSession();
    window.location.href = '/admin/login';
  };

  const value = {
    adminUser,
    masjid,
    loading,
    error,
    login,
    register,
    logout,
    refreshProfile: loadSession,
  };

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>;
};

export const useApp = () => {
  const context = useContext(AppContext);
  if (!context) {
    throw new Error('useApp must be used within AppProvider');
  }
  return context;
};
