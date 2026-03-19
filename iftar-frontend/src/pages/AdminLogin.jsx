import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Alert from '../components/Alert';
import { useApp } from '../context/AppContext';
import { getErrorMessage } from '../utils/helpers';

const DEMO_ADMIN_EMAIL = import.meta.env.VITE_DEMO_ADMIN_EMAIL || 'demo.admin@hackathon.local';
const DEMO_ADMIN_PASSWORD = import.meta.env.VITE_DEMO_ADMIN_PASSWORD || 'Demo12345!';

const AdminLogin = () => {
  const navigate = useNavigate();
  const { login } = useApp();
  const [formData, setFormData] = useState({
    email: '',
    password: '',
  });
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  const fillDemoCredentials = () => {
    setFormData({
      email: DEMO_ADMIN_EMAIL,
      password: DEMO_ADMIN_PASSWORD,
    });
  };

  const handleSubmit = async (event) => {
    event.preventDefault();

    try {
      setSubmitting(true);
      setError('');
      await login(formData);
      navigate('/dashboard');
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 px-4">
      <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8">
        <p className="text-sm text-primary-700 font-semibold mb-2">Panel Masjid</p>
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Log Masuk Admin</h1>
        <p className="text-gray-600 mb-6">Hanya admin masjid dibenarkan akses panel pengurusan.</p>

        <div className="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-4">
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-amber-800 mb-2">Demo Hackathon</p>
          <div className="text-sm text-amber-900 space-y-1">
            <p><span className="font-semibold">Username:</span> {DEMO_ADMIN_EMAIL}</p>
            <p><span className="font-semibold">Password:</span> {DEMO_ADMIN_PASSWORD}</p>
          </div>
          <button
            type="button"
            className="btn mt-3 bg-amber-900 text-white hover:bg-amber-800"
            onClick={fillDemoCredentials}
          >
            Guna Akaun Demo
          </button>
        </div>

        {error && <Alert type="error" message={error} onClose={() => setError('')} />}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="label">Email</label>
            <input
              className="input"
              type="email"
              value={formData.email}
              onChange={(e) => setFormData((current) => ({ ...current, email: e.target.value }))}
              required
            />
          </div>

          <div>
            <label className="label">Kata Laluan</label>
            <input
              className="input"
              type="password"
              value={formData.password}
              onChange={(e) => setFormData((current) => ({ ...current, password: e.target.value }))}
              required
            />
          </div>

          <button type="submit" className="btn btn-primary w-full" disabled={submitting}>
            {submitting ? 'Sedang log masuk...' : 'Log Masuk'}
          </button>
        </form>

        <div className="mt-6 flex items-center justify-between text-sm">
          <Link to="/" className="text-primary-700 hover:text-primary-800">
            Kembali ke utama
          </Link>
          <Link to="/admin/register" className="text-primary-700 hover:text-primary-800 font-semibold">
            Daftar admin baru
          </Link>
        </div>
      </div>
    </div>
  );
};

export default AdminLogin;
