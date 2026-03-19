import axios from 'axios';

const getAuthToken = () => localStorage.getItem('admin_token');

// Create axios instance with base configuration
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor - Add tenant ID from localStorage
api.interceptors.request.use(
  (config) => {
    const token = getAuthToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    const masjidId = localStorage.getItem('masjid_id');
    if (masjidId && !config.headers['X-Tenant-ID']) {
      config.headers['X-Tenant-ID'] = masjidId;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - Handle errors  globally
api.interceptors.response.use(
  (response) => {
    return response.data; // Return just the data object
  },
  (error) => {
    if (error.response) {
      // Server responded with error
      const errorData = error.response.data;
      
      // Handle specific status codes
      if (error.response.status === 401) {
        // Unauthorized admin session
        localStorage.removeItem('admin_token');
        localStorage.removeItem('masjid_id');
        localStorage.removeItem('masjid_name');

        if (window.location.pathname.startsWith('/dashboard') ||
            window.location.pathname.startsWith('/iftar-days') ||
            window.location.pathname.startsWith('/participants') ||
            window.location.pathname.startsWith('/sponsorships') ||
            window.location.pathname.startsWith('/profile')) {
          window.location.href = '/admin/login';
        }
      }
      
      return Promise.reject({
        message: errorData.message || 'Ralat berlaku',
        errors: errorData.errors || null,
        status: error.response.status,
      });
    } else if (error.request) {
      // Request made but no response
      return Promise.reject({
        message: 'Tiada sambungan ke pelayan. Sila semak internet anda.',
        errors: null,
        status: 0,
      });
    } else {
      // Something else happened
      return Promise.reject({
        message: error.message || 'Ralat tidak dijangka',
        errors: null,
        status: 0,
      });
    }
  }
);

export default api;

// API Endpoints
export const masjidAPI = {
  listPublic: () => api.get('/public/masjids'),
  listOpenIftarDaysPublic: (masjidId, params) => api.get(`/public/masjids/${masjidId}/iftar-days`, { params }),
  getProfile: () => api.get('/masjid/profile'),
  updateProfile: (data) => api.put('/masjid/profile', data),
  getDashboard: () => api.get('/masjid/dashboard'),
};

export const iftarDaysAPI = {
  getAll: (params) => api.get('/iftar-days', { params }),
  getOne: (id) => api.get(`/iftar-days/${id}`),
  create: (data) => api.post('/iftar-days', data),
  update: (id, data) => api.put(`/iftar-days/${id}`, data),
  delete: (id) => api.delete(`/iftar-days/${id}`),
  getStatistics: (id) => api.get(`/iftar-days/${id}/statistics`),
  close: (id) => api.post(`/iftar-days/${id}/close`),
  reopen: (id) => api.post(`/iftar-days/${id}/reopen`),
};

export const participantsAPI = {
  getAll: (params) => api.get('/participants', { params }),
  getOne: (id) => api.get(`/participants/${id}`),
  register: (data) => api.post('/participants', data),
  registerPublic: (masjidId, data) => api.post(`/public/masjids/${masjidId}/participants`, data),
  cancel: (id) => api.post(`/participants/${id}/cancel`),
  checkIn: (id) => api.post(`/participants/${id}/check-in`),
  noShow: (id) => api.post(`/participants/${id}/no-show`),
  checkEligibility: (phone) => api.post('/participants/check-eligibility', { no_telefon: phone }),
};

export const authAPI = {
  login: (data) => api.post('/admin/login', data),
  register: (data) => api.post('/admin/register', data),
  me: () => api.get('/admin/me'),
  logout: () => api.post('/admin/logout'),
};

export const superadminAPI = {
  getPendingAdmins: (masterKey) =>
    api.get('/superadmin/admins/pending', {
      headers: {
        'X-Master-Key': masterKey,
      },
    }),
  approveAdmin: (userId, masterKey) =>
    api.post(
      `/superadmin/admins/${userId}/approve`,
      {},
      {
        headers: {
          'X-Master-Key': masterKey,
        },
      }
    ),
};

export const sponsorshipsAPI = {
  getAll: (params) => api.get('/sponsorships', { params }),
  getOne: (id) => api.get(`/sponsorships/${id}`),
  create: (data) => api.post('/sponsorships', data),
  update: (id, data) => api.put(`/sponsorships/${id}`, data),
  delete: (id) => api.delete(`/sponsorships/${id}`),
  markAsPaid: (id) => api.post(`/sponsorships/${id}/mark-paid`),
  getStatistics: (params) => api.get('/sponsorships/statistics', { params }),
  getTopSponsors: (params) => api.get('/sponsorships/top-sponsors', { params }),
};
