import { useState, useEffect } from 'react';
import { masjidAPI } from '../api/client';
import Loading from '../components/Loading';
import Alert from '../components/Alert';
import { getErrorMessage } from '../utils/helpers';
import { useApp } from '../context/AppContext';

const Profile = () => {
  const { refreshProfile } = useApp();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [formData, setFormData] = useState({
    nama_masjid: '',
    alamat: '',
    negeri: '',
    contact_phone: '',
    logo_url: '',
  });

  useEffect(() => {
    loadProfile();
  }, []);

  const loadProfile = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await masjidAPI.getProfile();
      const masjid = response?.data || {};
      setFormData({
        nama_masjid: masjid.nama_masjid || '',
        alamat: masjid.alamat || '',
        negeri: masjid.negeri || '',
        contact_phone: masjid.contact_phone || '',
        logo_url: masjid.logo_url || '',
      });
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (e) => {
    e.preventDefault();
    try {
      setSaving(true);
      setError(null);
      setSuccess(null);
      await masjidAPI.updateProfile(formData);
      setSuccess('Profil masjid berjaya dikemas kini.');
      if (refreshProfile) refreshProfile();
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setSaving(false);
    }
  };

  const field = (key, value) => setFormData((prev) => ({ ...prev, [key]: value }));

  if (loading) return <Loading />;

  return (
    <div>
      <h1 className="text-3xl font-bold mb-6">Profil Masjid</h1>

      {error && <Alert type="error" message={error} onClose={() => setError(null)} />}
      {success && <Alert type="success" message={success} onClose={() => setSuccess(null)} />}

      <div className="card max-w-2xl">
        <form onSubmit={handleSave} className="space-y-5">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Nama Masjid</label>
            <input
              type="text"
              maxLength={255}
              className="input"
              value={formData.nama_masjid}
              onChange={(e) => field('nama_masjid', e.target.value)}
              placeholder="cth: Masjid Al-Hidayah"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
            <textarea
              className="input"
              rows={3}
              value={formData.alamat}
              onChange={(e) => field('alamat', e.target.value)}
              placeholder="Alamat penuh masjid"
            />
          </div>

          <div className="grid md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Negeri</label>
              <input
                type="text"
                maxLength={100}
                className="input"
                value={formData.negeri}
                onChange={(e) => field('negeri', e.target.value)}
                placeholder="cth: Selangor"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">No Telefon</label>
              <input
                type="text"
                maxLength={15}
                className="input"
                value={formData.contact_phone}
                onChange={(e) => field('contact_phone', e.target.value)}
                placeholder="03-XXXXXXXX"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">URL Logo</label>
            <input
              type="url"
              className="input"
              value={formData.logo_url}
              onChange={(e) => field('logo_url', e.target.value)}
              placeholder="https://example.com/logo.png"
            />
            <p className="text-xs text-gray-500 mt-1">Masukkan URL imej logo masjid (pilihan).</p>
          </div>

          <div className="flex justify-end pt-2">
            <button type="submit" className="btn btn-primary" disabled={saving}>
              {saving ? 'Menyimpan...' : 'Simpan Profil'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default Profile;
