import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { masjidAPI } from '../api/client';
import Loading from '../components/Loading';
import Alert from '../components/Alert';
import { formatCurrency, formatDate, getErrorMessage } from '../utils/helpers';
import StatusBadge from '../components/StatusBadge';

const SHOW_DEMO_UTILITY = (import.meta.env.VITE_SHOW_DEMO_UTILITY || 'true') === 'true';

const Dashboard = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [statistics, setStatistics] = useState(null);
  const [masjidInfo, setMasjidInfo] = useState(null);
  const [upcomingDays, setUpcomingDays] = useState([]);

  useEffect(() => {
    loadDashboard();
  }, []);

  const loadDashboard = async () => {
    try {
      setLoading(true);
      setError(null);

      const [dashboardResponse, profileResponse] = await Promise.all([
        masjidAPI.getDashboard(),
        masjidAPI.getProfile(),
      ]);

      setStatistics(dashboardResponse?.data?.statistics || null);
      setMasjidInfo(dashboardResponse?.data?.masjid || profileResponse?.data || null);

      const profileDays = profileResponse?.data?.iftar_days || [];
      setUpcomingDays(Array.isArray(profileDays) ? profileDays : []);
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <Loading />;
  if (error) return <Alert type="error" message={error} />;
  if (!statistics) return null;

  const stats = [
    {
      label: 'Jumlah Hari Iftar',
      value: statistics.total_iftar_days,
      color: 'bg-blue-500',
    },
    {
      label: 'Event Akan Datang',
      value: statistics.upcoming_events,
      color: 'bg-green-500',
    },
    {
      label: 'Jumlah Peserta',
      value: statistics.total_participants,
      color: 'bg-purple-500',
    },
    {
      label: 'Jumlah Rekod Tajaan',
      value: statistics.total_sponsorships,
      color: 'bg-teal-500',
    },
    {
      label: 'Jumlah Tajaan',
      value: formatCurrency(statistics.total_sponsorship_amount),
      color: 'bg-yellow-500',
    },
    {
      label: 'Tajaan Dibayar',
      value: formatCurrency(statistics.paid_sponsorship_amount),
      color: 'bg-green-500',
    },
    {
      label: 'Tajaan Menunggu',
      value: formatCurrency(statistics.pending_sponsorship_amount),
      color: 'bg-orange-500',
    },
  ];

  return (
    <div>
      <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
        <div>
          <h1 className="text-3xl font-bold">Dashboard</h1>
          {masjidInfo && (
            <p className="text-gray-600 mt-2">
              {masjidInfo.nama_masjid} • {masjidInfo.negeri}
            </p>
          )}
        </div>
        <button type="button" onClick={loadDashboard} className="btn btn-secondary">
          Muat Semula
        </button>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        {stats.map((stat, index) => (
          <div key={index} className="card">
            <div className="flex items-center">
              <div className={`${stat.color} rounded-full p-3 mr-4`}>
                <div className="w-6 h-6 bg-white rounded-sm"></div>
              </div>
              <div>
                <p className="text-sm text-gray-600">{stat.label}</p>
                <p className="text-2xl font-bold">{stat.value}</p>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Upcoming Iftar Days */}
      {upcomingDays.length > 0 ? (
        <div className="card mb-8">
          <h2 className="text-xl font-bold mb-4">Hari Iftar Akan Datang</h2>
          <div className="space-y-3">
            {upcomingDays.map((day) => (
              <div key={day.id} className="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                <div>
                  <p className="font-medium">{formatDate(day.tarikh)}</p>
                  <p className="text-sm text-gray-600">
                    {day.jumlah_daftar} / {day.kapasiti_max} peserta
                  </p>
                </div>
                <StatusBadge status={day.status} />
              </div>
            ))}
          </div>
        </div>
      ) : (
        <div className="card">
          <h2 className="text-xl font-bold mb-2">Hari Iftar Akan Datang</h2>
          <p className="text-gray-600">
            Tiada event akan datang buat masa ini. Anda boleh cipta event baru di halaman Hari Iftar.
          </p>
        </div>
      )}

      {SHOW_DEMO_UTILITY && (
        <div className="mt-8 rounded-2xl border border-amber-200 bg-amber-50 p-6">
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-amber-800 mb-2">Demo Utility</p>
          <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <p className="text-amber-900">
              Untuk sesi hackathon, gunakan halaman Superadmin Approval untuk semak admin pending dan luluskan onboarding.
            </p>
            <Link to="/superadmin/approvals" className="btn bg-amber-900 text-white hover:bg-amber-800">
              Buka Superadmin Approval
            </Link>
          </div>
        </div>
      )}
    </div>
  );
};

export default Dashboard;
