import { useState, useEffect } from 'react';
import { participantsAPI, iftarDaysAPI } from '../api/client';
import Loading from '../components/Loading';
import Alert from '../components/Alert';
import { formatDate, getErrorMessage } from '../utils/helpers';

const getParticipantStatusInfo = (participant) => {
  if (participant.is_cancelled) {
    return { label: 'Dibatal', className: 'bg-red-100 text-red-700' };
  }
  const s = String(participant.checkin_status || '').toUpperCase();
  if (s === 'CHECKED_IN') return { label: 'Hadir', className: 'bg-green-100 text-green-700' };
  if (s === 'NO_SHOW') return { label: 'Tidak Hadir', className: 'bg-gray-100 text-gray-700' };
  return { label: 'Menunggu', className: 'bg-blue-100 text-blue-700' };
};

const Participants = () => {
  const [participants, setParticipants] = useState([]);
  const [iftarDays, setIftarDays] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [filterIftarDay, setFilterIftarDay] = useState('');
  const [filterStatus, setFilterStatus] = useState('active');
  const [search, setSearch] = useState('');
  const [showMobileFilters, setShowMobileFilters] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 10 });
  const [formData, setFormData] = useState({
    iftar_day_id: '',
    nama: '',
    no_telefon: '',
    bil_pax: 1,
    notes: '',
  });

  useEffect(() => {
    loadData();
  }, [filterIftarDay, filterStatus, search, currentPage, perPage]);

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

  const loadData = async () => {
    try {
      setLoading(true);
      setError(null);
      const params = { per_page: perPage, page: currentPage };
      if (filterIftarDay) params.iftar_day_id = filterIftarDay;
      if (filterStatus === 'active') params.is_cancelled = 0;
      else if (filterStatus === 'cancelled') params.is_cancelled = 1;
      if (search.trim()) params.search = search.trim();

      const [participantsResp, daysResp] = await Promise.all([
        participantsAPI.getAll(params),
        iftarDaysAPI.getAll({ per_page: 100 }),
      ]);
      setParticipants(participantsResp.data || []);
      setMeta(participantsResp.meta || { current_page: 1, last_page: 1, total: 0, per_page: perPage });
      setIftarDays(daysResp.data || []);
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setLoading(false);
    }
  };

  const handleRegister = async (e) => {
    e.preventDefault();
    try {
      setSubmitting(true);
      setError(null);
      await participantsAPI.register({
        ...formData,
        iftar_day_id: parseInt(formData.iftar_day_id),
        bil_pax: parseInt(formData.bil_pax),
      });
      setSuccess('Peserta berjaya didaftarkan.');
      setShowForm(false);
      setFormData({ iftar_day_id: '', nama: '', no_telefon: '', bil_pax: 1, notes: '' });
      loadData();
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

  const handleAction = async (action, id) => {
    const labels = { checkin: 'Check-in peserta', noshow: 'Tandakan tidak hadir untuk', cancel: 'Batalkan pendaftaran' };
    if (!window.confirm(`${labels[action]} ini?`)) return;
    try {
      setError(null);
      if (action === 'cancel') await participantsAPI.cancel(id);
      else if (action === 'checkin') await participantsAPI.checkIn(id);
      else if (action === 'noshow') await participantsAPI.noShow(id);
      setSuccess('Rekod peserta berjaya dikemas kini.');
      loadData();
    } catch (err) {
      setError(getErrorMessage(err));
    }
  };

  const getDayLabel = (iftarDayId) => {
    const day = iftarDays.find((d) => d.id === iftarDayId);
    return day ? formatDate(day.tarikh) : `#${iftarDayId}`;
  };

  const openDays = iftarDays.filter((d) => d.status === 'open');

  return (
    <div>
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h1 className="text-3xl font-bold">Peserta</h1>
        <button type="button" onClick={() => setShowForm(true)} className="btn btn-primary">
          + Daftar Peserta
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

      {/* Filters */}
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
            <label className="text-sm font-medium text-gray-700 block mb-1">Carian</label>
            <input
              className="input"
              placeholder="Nama atau telefon"
              value={search}
              onChange={(e) => {
                setSearch(e.target.value);
                setCurrentPage(1);
              }}
            />
          </div>

          <div>
            <label className="text-sm font-medium text-gray-700 block mb-1">Hari Iftar</label>
            <select
              value={filterIftarDay}
              onChange={(e) => {
                setFilterIftarDay(e.target.value);
                setCurrentPage(1);
              }}
              className="input"
            >
              <option value="">Semua</option>
              {iftarDays.map((day) => (
                <option key={day.id} value={day.id}>
                  {formatDate(day.tarikh)}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="text-sm font-medium text-gray-700 block mb-1">Status</label>
            <select
              value={filterStatus}
              onChange={(e) => {
                setFilterStatus(e.target.value);
                setCurrentPage(1);
              }}
              className="input"
            >
              <option value="">Semua</option>
              <option value="active">Aktif</option>
              <option value="cancelled">Dibatal</option>
            </select>
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
        </div>
      </div>

      {/* Register Form Modal */}
      {showForm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
            <h2 className="text-xl font-bold mb-4">Daftar Peserta</h2>
            <form onSubmit={handleRegister} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Hari Iftar <span className="text-red-500">*</span>
                </label>
                <select
                  required
                  className="input"
                  value={formData.iftar_day_id}
                  onChange={(e) => setFormData({ ...formData, iftar_day_id: e.target.value })}
                >
                  <option value="">-- Pilih Hari Iftar --</option>
                  {openDays.map((day) => (
                    <option key={day.id} value={day.id}>
                      {formatDate(day.tarikh)} — {day.available_slots} slot tersedia
                    </option>
                  ))}
                </select>
                {openDays.length === 0 && (
                  <p className="text-xs text-orange-600 mt-1">Tiada hari iftar yang buka buat masa ini.</p>
                )}
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Nama <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  required
                  maxLength={255}
                  className="input"
                  value={formData.nama}
                  onChange={(e) => setFormData({ ...formData, nama: e.target.value })}
                  placeholder="Nama penuh peserta"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  No Telefon <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  required
                  maxLength={15}
                  className="input"
                  value={formData.no_telefon}
                  onChange={(e) => setFormData({ ...formData, no_telefon: e.target.value })}
                  placeholder="01X-XXXXXXX"
                />
                <p className="text-xs text-gray-500 mt-1">Format: 01X-XXXXXXX</p>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Bil. Pax <span className="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  min={1}
                  max={10}
                  required
                  className="input"
                  value={formData.bil_pax}
                  onChange={(e) => setFormData({ ...formData, bil_pax: e.target.value })}
                />
                <p className="text-xs text-gray-500 mt-1">Maks: 10 orang setiap pendaftaran</p>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Nota</label>
                <textarea
                  className="input"
                  rows={2}
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
                  {submitting ? 'Mendaftar...' : 'Daftar'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Table */}
      {loading ? (
        <Loading />
      ) : participants.length === 0 ? (
        <div className="card text-center py-12">
          <p className="text-gray-500 text-lg">Tiada peserta dijumpai.</p>
        </div>
      ) : (
        <>
          <div className="md:hidden space-y-3 mb-4">
            {participants.map((p) => {
              const statusInfo = getParticipantStatusInfo(p);
              const isPending =
                !p.is_cancelled &&
                String(p.checkin_status || '').toUpperCase() === 'PENDING';

              return (
                <div key={p.id} className="card p-4">
                  <div className="flex items-start justify-between gap-3 mb-2">
                    <div>
                      <p className="font-semibold text-gray-900">{p.nama}</p>
                      <p className="text-sm text-gray-600">{p.no_telefon}</p>
                    </div>
                    <span className={`px-2 py-1 text-xs font-semibold rounded-full ${statusInfo.className}`}>
                      {statusInfo.label}
                    </span>
                  </div>
                  <div className="text-sm text-gray-600 space-y-1">
                    <p>Bil Pax: {p.bil_pax}</p>
                    <p>Hari Iftar: {getDayLabel(p.iftar_day_id)}</p>
                  </div>

                  {isPending && (
                    <div className="mt-3 flex flex-wrap gap-2">
                      <button
                        onClick={() => handleAction('checkin', p.id)}
                        className="text-xs py-1 px-3 rounded bg-green-100 text-green-700 hover:bg-green-200 font-medium"
                      >
                        Hadir
                      </button>
                      <button
                        onClick={() => handleAction('noshow', p.id)}
                        className="text-xs py-1 px-3 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 font-medium"
                      >
                        Tak Hadir
                      </button>
                      <button
                        onClick={() => handleAction('cancel', p.id)}
                        className="text-xs py-1 px-3 rounded bg-red-100 text-red-700 hover:bg-red-200 font-medium"
                      >
                        Batal
                      </button>
                    </div>
                  )}
                </div>
              );
            })}
          </div>

          <div className="card overflow-x-auto p-0 hidden md:block">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-200 bg-gray-50">
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Nama</th>
                <th className="text-left py-3 px-4 font-semibold text-gray-700">No Telefon</th>
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Bil Pax</th>
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Hari Iftar</th>
                <th className="text-right py-3 px-4 font-semibold text-gray-700">Aksi</th>
              </tr>
            </thead>
            <tbody>
              {participants.map((p) => {
                const statusInfo = getParticipantStatusInfo(p);
                const isPending =
                  !p.is_cancelled &&
                  String(p.checkin_status || '').toUpperCase() === 'PENDING';
                return (
                  <tr key={p.id} className="border-b border-gray-100 hover:bg-gray-50">
                    <td className="py-3 px-4 font-medium">{p.nama}</td>
                    <td className="py-3 px-4">{p.no_telefon}</td>
                    <td className="py-3 px-4">{p.bil_pax}</td>
                    <td className="py-3 px-4">
                      <span
                        className={`px-2 py-1 text-xs font-semibold rounded-full ${statusInfo.className}`}
                      >
                        {statusInfo.label}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-gray-600">{getDayLabel(p.iftar_day_id)}</td>
                    <td className="py-3 px-4">
                      {isPending && (
                        <div className="flex gap-2 justify-end flex-wrap">
                          <button
                            onClick={() => handleAction('checkin', p.id)}
                            className="text-xs py-1 px-3 rounded bg-green-100 text-green-700 hover:bg-green-200 font-medium"
                          >
                            Hadir
                          </button>
                          <button
                            onClick={() => handleAction('noshow', p.id)}
                            className="text-xs py-1 px-3 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 font-medium"
                          >
                            Tak Hadir
                          </button>
                          <button
                            onClick={() => handleAction('cancel', p.id)}
                            className="text-xs py-1 px-3 rounded bg-red-100 text-red-700 hover:bg-red-200 font-medium"
                          >
                            Batal
                          </button>
                        </div>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>

          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 border-t border-gray-100 text-sm">
            <p className="text-gray-600">
              Paparan {participants.length} rekod dari {meta.total} jumlah.
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

export default Participants;
