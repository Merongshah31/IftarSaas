import { useState } from 'react';
import { Link } from 'react-router-dom';
import Alert from '../components/Alert';
import { superadminAPI } from '../api/client';
import { formatDateTime, getErrorMessage } from '../utils/helpers';

const STORAGE_KEY = 'superadmin_master_key';

const SuperadminApprovals = () => {
  const [masterKey, setMasterKey] = useState(localStorage.getItem(STORAGE_KEY) || '');
  const [pendingAdmins, setPendingAdmins] = useState([]);
  const [loading, setLoading] = useState(false);
  const [approvingId, setApprovingId] = useState(null);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const fetchPending = async () => {
    try {
      if (!masterKey.trim()) {
        setError('Sila masukkan Master Key terlebih dahulu.');
        return;
      }

      setLoading(true);
      setError('');
      setSuccess('');

      const response = await superadminAPI.getPendingAdmins(masterKey.trim());
      setPendingAdmins(Array.isArray(response.data) ? response.data : []);
      localStorage.setItem(STORAGE_KEY, masterKey.trim());
    } catch (err) {
      setError(getErrorMessage(err));
      setPendingAdmins([]);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (userId) => {
    try {
      setApprovingId(userId);
      setError('');
      setSuccess('');

      const response = await superadminAPI.approveAdmin(userId, masterKey.trim());
      setSuccess(response.message || 'Akaun admin telah diluluskan.');
      setPendingAdmins((current) => current.filter((admin) => admin.id !== userId));
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setApprovingId(null);
    }
  };

  const handleClearKey = () => {
    localStorage.removeItem(STORAGE_KEY);
    setMasterKey('');
    setPendingAdmins([]);
    setError('');
    setSuccess('');
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white px-4 py-10">
      <div className="max-w-6xl mx-auto">
        <div className="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <p className="text-xs uppercase tracking-[0.25em] text-slate-300 mb-2">Superadmin Control</p>
            <h1 className="text-3xl md:text-4xl font-bold">Kelulusan Akaun Admin</h1>
            <p className="text-slate-300 mt-2">Semak akaun pending dan luluskan onboarding admin masjid.</p>
          </div>
          <Link to="/" className="btn btn-secondary bg-white/90 text-gray-900 hover:bg-white">
            Kembali
          </Link>
        </div>

        {error && <Alert type="error" message={error} onClose={() => setError('')} />}
        {success && <Alert type="success" message={success} onClose={() => setSuccess('')} />}

        <div className="bg-white/10 border border-white/20 rounded-2xl p-6 mb-8 backdrop-blur">
          <label className="label text-white">Master Key</label>
          <div className="grid md:grid-cols-[1fr_auto_auto] gap-3 items-end">
            <input
              className="input bg-white text-gray-900"
              type="password"
              value={masterKey}
              onChange={(event) => setMasterKey(event.target.value)}
              placeholder="Masukkan Master Key"
              autoComplete="off"
            />
            <button type="button" className="btn btn-primary" onClick={fetchPending} disabled={loading}>
              {loading ? 'Memuat...' : 'Muat Pending'}
            </button>
            <button type="button" className="btn btn-secondary" onClick={handleClearKey}>
              Padam Key
            </button>
          </div>
          <p className="text-xs text-slate-300 mt-3">
            Key disimpan sementara dalam browser local storage untuk sesi demo ini.
          </p>
        </div>

        <div className="bg-white text-gray-900 rounded-2xl shadow-2xl overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 className="text-xl font-semibold">Senarai Pending Admin</h2>
            <span className="text-sm text-gray-500">Jumlah: {pendingAdmins.length}</span>
          </div>

          {pendingAdmins.length === 0 ? (
            <div className="p-8 text-center text-gray-500">
              Tiada admin pending. Tekan "Muat Pending" selepas ada pendaftaran baharu.
            </div>
          ) : (
            <>
              <div className="md:hidden p-4 space-y-3">
                {pendingAdmins.map((admin) => (
                  <div key={admin.id} className="rounded-xl border border-gray-200 p-4">
                    <div className="flex items-start justify-between gap-3 mb-2">
                      <div>
                        <p className="font-semibold text-gray-900">{admin.name}</p>
                        <p className="text-sm text-gray-600">{admin.email}</p>
                      </div>
                      <span className="text-xs font-medium bg-gray-100 px-2 py-1 rounded">ID {admin.id}</span>
                    </div>
                    <div className="text-sm text-gray-600 space-y-1">
                      <p>Masjid: {admin.masjid?.nama_masjid || '-'}</p>
                      <p>Tarikh Daftar: {admin.created_at ? formatDateTime(admin.created_at) : '-'}</p>
                    </div>
                    <button
                      type="button"
                      className="btn btn-primary mt-3"
                      onClick={() => handleApprove(admin.id)}
                      disabled={approvingId === admin.id || !masterKey.trim()}
                    >
                      {approvingId === admin.id ? 'Meluluskan...' : 'Luluskan'}
                    </button>
                  </div>
                ))}
              </div>

              <div className="overflow-x-auto hidden md:block">
              <table className="min-w-full text-sm">
                <thead className="bg-gray-50 text-gray-700">
                  <tr>
                    <th className="text-left px-4 py-3">ID</th>
                    <th className="text-left px-4 py-3">Nama</th>
                    <th className="text-left px-4 py-3">Email</th>
                    <th className="text-left px-4 py-3">Masjid</th>
                    <th className="text-left px-4 py-3">Tarikh Daftar</th>
                    <th className="text-right px-4 py-3">Tindakan</th>
                  </tr>
                </thead>
                <tbody>
                  {pendingAdmins.map((admin) => (
                    <tr key={admin.id} className="border-t border-gray-100">
                      <td className="px-4 py-3 font-medium">{admin.id}</td>
                      <td className="px-4 py-3">{admin.name}</td>
                      <td className="px-4 py-3">{admin.email}</td>
                      <td className="px-4 py-3">{admin.masjid?.nama_masjid || '-'}</td>
                      <td className="px-4 py-3">{admin.created_at ? formatDateTime(admin.created_at) : '-'}</td>
                      <td className="px-4 py-3 text-right">
                        <button
                          type="button"
                          className="btn btn-primary"
                          onClick={() => handleApprove(admin.id)}
                          disabled={approvingId === admin.id || !masterKey.trim()}
                        >
                          {approvingId === admin.id ? 'Meluluskan...' : 'Luluskan'}
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default SuperadminApprovals;
