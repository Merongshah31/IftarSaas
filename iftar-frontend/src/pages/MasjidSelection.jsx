import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useApp } from '../context/AppContext';
import Alert from '../components/Alert';
import Loading from '../components/Loading';
import { masjidAPI } from '../api/client';
import { getErrorMessage } from '../utils/helpers';

const MasjidSelection = () => {
  const [selectedId, setSelectedId] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const [masjids, setMasjids] = useState([]);
  const { selectMasjid } = useApp();
  const navigate = useNavigate();

  useEffect(() => {
    const loadMasjids = async () => {
      try {
        setLoading(true);
        setError('');
        const response = await masjidAPI.listPublic();
        setMasjids(response.data || []);
      } catch (err) {
        setError(getErrorMessage(err));
      } finally {
        setLoading(false);
      }
    };

    loadMasjids();
  }, []);

  const handleSubmit = (e) => {
    e.preventDefault();
    
    if (!selectedId) {
      setError('Sila pilih masjid');
      return;
    }

    selectMasjid(selectedId);
    navigate('/dashboard');
  };

  if (loading) {
    return <Loading />;
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-700">
      <div className="max-w-md w-full mx-4">
        <div className="text-center mb-8">
          <h1 className="text-4xl font-bold text-white mb-2">🕌</h1>
          <h2 className="text-3xl font-bold text-white">Sistem Pengurusan Iftar</h2>
          <p className="text-primary-100 mt-2">Pilih masjid anda untuk meneruskan</p>
        </div>

        <div className="bg-white rounded-lg shadow-xl p-8">
          {error && <Alert type="error" message={error} onClose={() => setError('')} />}

          <form onSubmit={handleSubmit}>
            <div className="mb-6">
              <label className="label">Pilih Masjid</label>
              <select
                value={selectedId}
                onChange={(e) => setSelectedId(e.target.value)}
                className="input"
              >
                <option value="">-- Sila Pilih --</option>
                {masjids.map((masjid) => (
                  <option key={masjid.id} value={masjid.id}>
                    {masjid.nama_masjid} ({masjid.negeri})
                  </option>
                ))}
              </select>
            </div>

            <button type="submit" className="w-full btn btn-primary">
              Teruskan
            </button>
          </form>

          <div className="mt-6 text-center text-sm text-gray-600">
            <p>Pilih masjid sebenar untuk membuka panel pengurusan.</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default MasjidSelection;
