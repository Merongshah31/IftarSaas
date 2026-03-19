import { useState, useEffect } from 'react';
import { iftarDaysAPI } from '../api/client';
import Loading from '../components/Loading';
import Alert from '../components/Alert';
import StatusBadge from '../components/StatusBadge';
import { formatDate, getErrorMessage } from '../utils/helpers';

const IftarDays = () => {
  const [iftarDays, setIftarDays] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [filterStatus, setFilterStatus] = useState('');
  const [showMobileFilters, setShowMobileFilters] = useState(true);
  const [fromDate, setFromDate] = useState('');
  const [toDate, setToDate] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 10 });
  const [formData, setFormData] = useState({
    tarikh: '',
    kapasiti_max: 100,
    notes: '',
  });

  useEffect(() => {
    loadIftarDays();
  }, [filterStatus, fromDate, toDate, currentPage, perPage]);

  useEffect(() => {
    const handleScroll = () => {
      if (window.innerWidth < 768 && window.scrollY > 100) {
        setShowMobileFilters(false);
      }
    };

    window.addEventListener('scroll', handleScroll, { passive: true });

    return () => {
      window.removeEventListener('scroll', handleScroll);
    };
  }, []);

  const loadIftarDays = async () => {
    try {
      setLoading(true);
      setError(null);
      const params = { per_page: perPage, page: currentPage };
      if (filterStatus) params.status = filterStatus;
      if (fromDate) params.from_date = fromDate;
      if (toDate) params.to_date = toDate;
      const response = await iftarDaysAPI.getAll(params);
      setIftarDays(response.data || []);
      setMeta(response.meta || { current_page: 1, last_page: 1, total: 0, per_page: perPage });
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setLoading(false);
    }
  };

  const handleCreate = async (e) => {
    e.preventDefault();
    try {
      setSubmitting(true);
      setError(null);
      await iftarDaysAPI.create({
        ...formData,
        kapasiti_max: parseInt(formData.kapasiti_max),
      });
      setSuccess('Hari Iftar berjaya dicipta.');
      setShowForm(false);
      setFormData({ tarikh: '', kapasiti_max: 100, notes: '' });
      loadIftarDays();
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

  const handleClose = async (id) => {
    if (!window.confirm('Tutup hari iftar ini? Pendaftaran baharu tidak akan diterima.')) return;
    try {
      setError(null);
      await iftarDaysAPI.close(id);
      setSuccess('Hari Iftar berjaya ditutup.');
      loadIftarDays();
    } catch (err) {
      setError(getErrorMessage(err));
    }
  };

  const handleReopen = async (id) => {
    if (!window.confirm('Buka semula hari iftar ini?')) return;
    try {
      setError(null);
      await iftarDaysAPI.reopen(id);
      setSuccess('Hari Iftar berjaya dibuka semula.');
      loadIftarDays();
    } catch (err) {
      setError(getErrorMessage(err));
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Padam hari iftar ini? Tindakan ini tidak boleh dibatalkan.')) return;
    try {
      setError(null);
      await iftarDaysAPI.delete(id);
      setSuccess('Hari Iftar berjaya dipadam.');
      loadIftarDays();
    } catch (err) {
      setError(getErrorMessage(err));
    }
  };

  const today = new Date().toISOString().split('T')[0];

  return (
    <div>
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h1 className="text-3xl font-bold">Hari Iftar</h1>
        <button type="button" onClick={() => setShowForm(true)} className="btn btn-primary">
          + Tambah Hari Iftar
        </button>
      </div>

      {error && <Alert type="error" message={error} onClose={() => setError(null)} />}
      {success && <Alert type="success" message={success} onClose={() => setSuccess(null)} />}

      <div className="md:hidden sticky top-2 z-30 mb-3">
        <button
          type="button"
          className="btn btn-secondary w-full"
          onClick={() => setShowMobileFilters((current) => !current)}
        >
          {showMobileFilters ? 'Sembunyikan Filter' : 'Tapis & Carian'}
        </button>
      </div>

      {/* Filter */}
      <div
        className={`card mb-6 md:static md:z-auto md:bg-white md:backdrop-blur-none ${
          showMobileFilters ? 'block sticky top-14 z-20 bg-white/95 backdrop-blur' : 'hidden md:block'
        }`}
      >
        <div className="md:hidden flex justify-end mb-3">
          <button
            type="button"
            className="text-xs px-3 py-1 rounded bg-gray-100 text-gray-700"
            onClick={() => setShowMobileFilters(false)}
          >
            Tutup
          </button>
        </div>
        <div className="grid md:grid-cols-4 gap-4 items-end">
          <div>
            <label className="text-sm font-medium text-gray-700 block mb-1">Tapis Status</label>
            <select
              value={filterStatus}
              onChange={(e) => {
                setFilterStatus(e.target.value);
                setCurrentPage(1);
              }}
              className="input"
            >
              <option value="">Semua</option>
              <option value="open">Buka</option>
              <option value="full">Penuh</option>
              <option value="closed">Ditutup</option>
            </select>
          </div>

          <div>
            <label className="text-sm font-medium text-gray-700 block mb-1">Dari Tarikh</label>
            <input
              type="date"
              className="input"
              value={fromDate}
              onChange={(e) => {
                setFromDate(e.target.value);
                setCurrentPage(1);
              }}
            />
          </div>

          <div>
            <label className="text-sm font-medium text-gray-700 block mb-1">Ke Tarikh</label>
            <input
              type="date"
              className="input"
              value={toDate}
              onChange={(e) => {
                setToDate(e.target.value);
                setCurrentPage(1);
              }}
            />
          </div>

          <div>
            <label className="text-sm font-medium text-gray-700 block mb-1">Per Halaman</label>
            <select
              value={perPage}
              onChange={(e) => {
                setPerPage(parseInt(e.target.value, 10));
                setCurrentPage(1);
              }}
              className="input"
            >
              <option value={10}>10</option>
              <option value={25}>25</option>
              <option value={50}>50</option>
            </select>
          </div>

          <button
            type="button"
            className="btn btn-secondary md:col-span-4"
            onClick={() => {
              setFilterStatus('');
              setFromDate('');
              setToDate('');
              setCurrentPage(1);
            }}
          >
            Reset Filter
          </button>
        </div>
      </div>

      {/* Create Form Modal */}
      {showForm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
            <h2 className="text-xl font-bold mb-4">Tambah Hari Iftar</h2>
            <form onSubmit={handleCreate} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Tarikh <span className="text-red-500">*</span>
                </label>
                <input
                  type="date"
                  min={today}
                  required
                  className="input"
                  value={formData.tarikh}
                  onChange={(e) => setFormData({ ...formData, tarikh: e.target.value })}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Kapasiti Maksimum <span className="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  min={10}
                  max={1000}
                  required
                  className="input"
                  value={formData.kapasiti_max}
                  onChange={(e) => setFormData({ ...formData, kapasiti_max: e.target.value })}
                />
                <p className="text-xs text-gray-500 mt-1">Min: 10, Maks: 1000 orang</p>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Nota</label>
                <textarea
                  className="input"
                  rows={3}
                  maxLength={500}
                  value={formData.notes}
                  onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                  placeholder="Nota tambahan (pilihan)"
                />
              </div>
              <div className="sticky bottom-0 bg-white pt-3 pb-[calc(env(safe-area-inset-bottom)+0.25rem)] -mx-1 px-1 flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                <button
                  type="button"
                  onClick={() => setShowForm(false)}
                  className="btn btn-secondary w-full sm:w-auto"
                  disabled={submitting}
                >
                  Batal
                </button>
                <button type="submit" className="btn btn-primary w-full sm:w-auto" disabled={submitting}>
                  {submitting ? 'Menyimpan...' : 'Simpan'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Table */}
      {loading ? (
        <Loading />
      ) : iftarDays.length === 0 ? (
        <div className="card text-center py-12">
          <p className="text-gray-500 text-lg">Tiada hari iftar dijumpai.</p>
          <p className="text-gray-400 mt-1">Klik &quot;Tambah Hari Iftar&quot; untuk mencipta rekod pertama.</p>
        </div>
      ) : (
        <>
          <div className="md:hidden space-y-3 mb-4">
            {iftarDays.map((day) => (
              <div key={day.id} className="card p-4">
                <div className="flex items-start justify-between gap-3 mb-2">
                  <p className="font-semibold text-gray-900">{formatDate(day.tarikh)}</p>
                  <StatusBadge status={day.status} />
                </div>
                <div className="text-sm text-gray-600 space-y-1">
                  <p>Daftar/Kapasiti: {day.jumlah_daftar} / {day.kapasiti_max}</p>
                  <p>Slot Tinggal: {day.available_slots}</p>
                  <p>Nota: {day.notes || '-'}</p>
                </div>
                <div className="mt-3 flex flex-wrap gap-2">
                  {day.status !== 'closed' && (
                    <button
                      onClick={() => handleClose(day.id)}
                      className="btn btn-secondary text-xs py-1 px-3"
                    >
                      Tutup
                    </button>
                  )}
                  {day.status === 'closed' && (
                    <button
                      onClick={() => handleReopen(day.id)}
                      className="btn btn-secondary text-xs py-1 px-3"
                    >
                      Buka Semula
                    </button>
                  )}
                  <button
                    onClick={() => handleDelete(day.id)}
                    className="text-xs py-1 px-3 rounded bg-red-100 text-red-700 hover:bg-red-200 font-medium"
                  >
                    Padam
                  </button>
                </div>
              </div>
            ))}
          </div>

          <div className="card overflow-x-auto p-0 hidden md:block">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-200 bg-gray-50">
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Tarikh</th>
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Daftar / Kapasiti</th>
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Slot Tinggal</th>
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Nota</th>
                <th className="text-right py-3 px-4 font-semibold text-gray-700">Aksi</th>
              </tr>
            </thead>
            <tbody>
              {iftarDays.map((day) => (
                <tr key={day.id} className="border-b border-gray-100 hover:bg-gray-50">
                  <td className="py-3 px-4 font-medium">{formatDate(day.tarikh)}</td>
                  <td className="py-3 px-4">
                    <StatusBadge status={day.status} />
                  </td>
                  <td className="py-3 px-4">
                    {day.jumlah_daftar} / {day.kapasiti_max}
                  </td>
                  <td className="py-3 px-4">{day.available_slots}</td>
                  <td className="py-3 px-4 text-gray-500 max-w-xs truncate">{day.notes || '-'}</td>
                  <td className="py-3 px-4">
                    <div className="flex gap-2 justify-end flex-wrap">
                      {day.status !== 'closed' && (
                        <button
                          onClick={() => handleClose(day.id)}
                          className="btn btn-secondary text-xs py-1 px-3"
                        >
                          Tutup
                        </button>
                      )}
                      {day.status === 'closed' && (
                        <button
                          onClick={() => handleReopen(day.id)}
                          className="btn btn-secondary text-xs py-1 px-3"
                        >
                          Buka Semula
                        </button>
                      )}
                      <button
                        onClick={() => handleDelete(day.id)}
                        className="text-xs py-1 px-3 rounded bg-red-100 text-red-700 hover:bg-red-200 font-medium"
                      >
                        Padam
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 border-t border-gray-100 text-sm">
            <p className="text-gray-600">
              Paparan {iftarDays.length} rekod dari {meta.total} jumlah.
            </p>
            <div className="flex items-center gap-2">
              <button
                type="button"
                className="btn btn-secondary text-xs py-1 px-3"
                disabled={meta.current_page <= 1}
                onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
              >
                Sebelum
              </button>
              <span className="text-gray-700">
                Halaman {meta.current_page} / {meta.last_page}
              </span>
              <button
                type="button"
                className="btn btn-secondary text-xs py-1 px-3"
                disabled={meta.current_page >= meta.last_page}
                onClick={() => setCurrentPage((p) => Math.min(meta.last_page, p + 1))}
              >
                Seterusnya
              </button>
            </div>
          </div>
        </div>
        </>
      )}
    </div>
  );
};

export default IftarDays;
