import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Alert from '../components/Alert';
import Loading from '../components/Loading';
import { masjidAPI, authAPI } from '../api/client';
import { getErrorMessage } from '../utils/helpers';

const AdminRegister = () => {
  const navigate = useNavigate();
  const [masjids, setMasjids] = useState([]);
  const [loadingMasjids, setLoadingMasjids] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [registered, setRegistered] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    masjid_id: '',
    invite_code: '',
    password: '',
    password_confirmation: '',
  });

  useEffect(() => {
    const loadMasjids = async () => {
      try {
        setLoadingMasjids(true);
        const response = await masjidAPI.listPublic();
        setMasjids(response.data || []);
      } catch (err) {
        setError(getErrorMessage(err));
      } finally {
        setLoadingMasjids(false);
      }
    };

    loadMasjids();
  }, []);

  const handleSubmit = async (event) => {
    event.preventDefault();

    try {
      setSubmitting(true);
      setError('');
      await authAPI.register({
        ...formData,
        masjid_id: parseInt(formData.masjid_id, 10),
      });
      setRegistered(true);
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

  if (loadingMasjids) {
    return <Loading />;
  }

  if (registered) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 px-4 py-10">
        <div className="w-full max-w-xl bg-white rounded-2xl shadow-2xl p-8 text-center">
          <div className="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Permohonan Diterima</h2>
          <p className="text-gray-600 mb-6">
            Akaun admin anda telah berjaya didaftarkan dan sedang menunggu kelulusan daripada superadmin.
            Anda akan dapat log masuk setelah akaun diluluskan.
          </p>
          <p className="text-sm text-gray-500 mb-6">
            Sila hubungi pentadbir sistem untuk mempercepatkan proses kelulusan.
          </p>
          <Link to="/admin/login" className="btn btn-primary inline-block">
            Pergi ke Log Masuk
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 px-4 py-10">
      <div className="w-full max-w-xl bg-white rounded-2xl shadow-2xl p-8">
        <p className="text-sm text-primary-700 font-semibold mb-2">Panel Masjid</p>
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Daftar Admin</h1>
        <p className="text-gray-600 mb-6">Cipta akaun pentadbir dan pautkan kepada masjid anda.</p>

        {error && <Alert type="error" message={error} onClose={() => setError('')} />}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid md:grid-cols-2 gap-4">
            <div>
              <label className="label">Nama Penuh</label>
              <input
                className="input"
                type="text"
                value={formData.name}
                onChange={(e) => setFormData((current) => ({ ...current, name: e.target.value }))}
                required
              />
            </div>

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
          </div>

          <div>
            <label className="label">Masjid</label>
            <select
              className="input"
              value={formData.masjid_id}
              onChange={(e) => setFormData((current) => ({ ...current, masjid_id: e.target.value }))}
              required
            >
              <option value="">-- Pilih Masjid --</option>
              {masjids.map((masjid) => (
                <option key={masjid.id} value={masjid.id}>
                  {masjid.nama_masjid} ({masjid.negeri})
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="label">Invite Code Admin</label>
            <input
              className="input"
              type="text"
              value={formData.invite_code}
              onChange={(e) => setFormData((current) => ({ ...current, invite_code: e.target.value }))}
              required
              maxLength={120}
              placeholder="Masukkan invite code"
            />
            <p className="text-xs text-gray-500 mt-1">Dapatkan code ini daripada pemilik sistem/superadmin.</p>
          </div>

          <div className="grid md:grid-cols-2 gap-4">
            <div>
              <label className="label">Kata Laluan</label>
              <input
                className="input"
                type="password"
                value={formData.password}
                onChange={(e) => setFormData((current) => ({ ...current, password: e.target.value }))}
                required
                minLength={8}
              />
            </div>

            <div>
              <label className="label">Sahkan Kata Laluan</label>
              <input
                className="input"
                type="password"
                value={formData.password_confirmation}
                onChange={(e) => setFormData((current) => ({ ...current, password_confirmation: e.target.value }))}
                required
                minLength={8}
              />
            </div>
          </div>

          <button type="submit" className="btn btn-primary w-full" disabled={submitting || !formData.masjid_id || !formData.invite_code}>
            {submitting ? 'Mendaftar...' : 'Daftar Admin'}
          </button>
        </form>

        <div className="mt-6 flex items-center justify-between text-sm">
          <Link to="/" className="text-primary-700 hover:text-primary-800">
            Kembali ke utama
          </Link>
          <Link to="/admin/login" className="text-primary-700 hover:text-primary-800 font-semibold">
            Sudah ada akaun?
          </Link>
        </div>
      </div>
    </div>
  );
};

export default AdminRegister;
