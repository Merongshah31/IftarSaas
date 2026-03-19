import { useState, useEffect } from 'react';
import { sponsorshipsAPI, iftarDaysAPI } from '../api/client';
import Loading from '../components/Loading';
import Alert from '../components/Alert';
import { formatDate, formatCurrency, getErrorMessage } from '../utils/helpers';

const EMPTY_FORM = {
  iftar_day_id: '',
  nama_sponsor: '',
  phone: '',
  jumlah_tajaan: '',
  jenis_tajaan: 'IFTAR',
  sponsor_coverage: 'PARTIAL',
  payment_status: 'PENDING',
  notes: '',
};

const paymentBadge = (status) => {
  const s = String(status || '').toUpperCase();
  if (s === 'PAID') return 'bg-green-100 text-green-700';
  if (s === 'FAILED') return 'bg-red-100 text-red-700';
  return 'bg-yellow-100 text-yellow-700';
};

const paymentLabel = (status) => {
  const s = String(status || '').toUpperCase();
  if (s === 'PAID') return 'Dibayar';
  if (s === 'FAILED') return 'Gagal';
  return 'Menunggu';
};

const Sponsorships = () => {
  const [sponsorships, setSponsorships] = useState([]);
  const [iftarDays, setIftarDays] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [editId, setEditId] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [filterStatus, setFilterStatus] = useState('');
  const [search, setSearch] = useState('');
  const [showMobileFilters, setShowMobileFilters] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 10 });
  const [formData, setFormData] = useState(EMPTY_FORM);

  useEffect(() => {
    loadData();
  }, [filterStatus, search, currentPage, perPage]);

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
      if (filterStatus) params.payment_status = filterStatus;
      if (search.trim()) params.search = search.trim();
      const [sponsResp, daysResp] = await Promise.all([
        sponsorshipsAPI.getAll(params),
        iftarDaysAPI.getAll({ per_page: 100 }),
      ]);
      setSponsorships(sponsResp.data || []);
      setMeta(sponsResp.meta || { current_page: 1, last_page: 1, total: 0, per_page: perPage });
      setIftarDays(daysResp.data || []);
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setLoading(false);
    }
  };

  const openCreate = () => {
    setEditId(null);
    setFormData(EMPTY_FORM);
    setShowForm(true);
  };

  const openEdit = (s) => {
    setEditId(s.id);
    setFormData({
      iftar_day_id: s.iftar_day_id || '',
      nama_sponsor: s.nama_sponsor,
      phone: s.phone,
      jumlah_tajaan: s.jumlah_tajaan,
      jenis_tajaan: s.jenis_tajaan,
      sponsor_coverage: s.sponsor_coverage,
      payment_status: s.payment_status,
      notes: s.notes || '',
    });
    setShowForm(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setSubmitting(true);
      setError(null);
      const payload = {
        ...formData,
        iftar_day_id: formData.iftar_day_id ? parseInt(formData.iftar_day_id) : null,
        jumlah_tajaan: parseFloat(formData.jumlah_tajaan),
      };
      if (editId) {
        await sponsorshipsAPI.update(editId, payload);
        setSuccess('Rekod tajaan berjaya dikemas kini.');
      } else {
        await sponsorshipsAPI.create(payload);
        setSuccess('Rekod tajaan berjaya dicipta.');
      }
      setShowForm(false);
      setEditId(null);
      loadData();
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

  const handleMarkPaid = async (id) => {
    if (!window.confirm('Tandakan tajaan ini sebagai DIBAYAR? Status ini tidak boleh diubah selepas ini.')) return;
    try {
      setError(null);
      await sponsorshipsAPI.markAsPaid(id);
      setSuccess('Tajaan berjaya ditandakan sebagai dibayar.');
      loadData();
    } catch (err) {
      setError(getErrorMessage(err));
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Padam rekod tajaan ini?')) return;
    try {
      setError(null);
      await sponsorshipsAPI.delete(id);
      setSuccess('Rekod tajaan berjaya dipadam.');
      loadData();
    } catch (err) {
      setError(getErrorMessage(err));
    }
  };

  const getDayLabel = (iftarDayId) => {
    if (!iftarDayId) return 'Am';
    const day = iftarDays.find((d) => d.id === iftarDayId);
    return day ? formatDate(day.tarikh) : `#${iftarDayId}`;
  };

  const isPaid = (s) => String(s.payment_status || '').toUpperCase() === 'PAID';

  return (
    <div>
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h1 className="text-3xl font-bold">Tajaan</h1>
        <button type="button" onClick={openCreate} className="btn btn-primary">
          + Tambah Tajaan
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
        <div className="grid md:grid-cols-3 gap-4 items-end">
          <div>
            <label className="text-sm font-medium text-gray-700 block mb-1">Carian</label>
            <input
              className="input"
              placeholder="Nama sponsor atau telefon"
              value={search}
              onChange={(e) => {
                setSearch(e.target.value);
                setCurrentPage(1);
              }}
            />
          </div>
          <div>
            <label className="text-sm font-medium text-gray-700 block mb-1">Status Bayaran</label>
            <select
              value={filterStatus}
              onChange={(e) => {
                setFilterStatus(e.target.value);
                setCurrentPage(1);
              }}
              className="input"
            >
              <option value="">Semua</option>
              <option value="PENDING">Menunggu</option>
              <option value="PAID">Dibayar</option>
              <option value="FAILED">Gagal</option>
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

      {/* Form Modal */}
      {showForm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
            <h2 className="text-xl font-bold mb-4">
              {editId ? 'Kemaskini Tajaan' : 'Tambah Tajaan'}
            </h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Hari Iftar (Pilihan)
                </label>
                <select
                  className="input"
                  value={formData.iftar_day_id}
                  onChange={(e) => setFormData({ ...formData, iftar_day_id: e.target.value })}
                >
                  <option value="">-- Tajaan Am (tanpa tarikh spesifik) --</option>
                  {iftarDays.map((day) => (
                    <option key={day.id} value={day.id}>
                      {formatDate(day.tarikh)}
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Nama Sponsor <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    maxLength={255}
                    className="input"
                    value={formData.nama_sponsor}
                    onChange={(e) => setFormData({ ...formData, nama_sponsor: e.target.value })}
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
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    placeholder="01X-XXXXXXX"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Jumlah Tajaan (RM) <span className="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  required
                  min={10}
                  max={100000}
                  step="0.01"
                  className="input"
                  value={formData.jumlah_tajaan}
                  onChange={(e) => setFormData({ ...formData, jumlah_tajaan: e.target.value })}
                  placeholder="cth: 500.00"
                />
                <p className="text-xs text-gray-500 mt-1">Min: RM10, Maks: RM100,000</p>
              </div>

              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Jenis Tajaan <span className="text-red-500">*</span>
                  </label>
                  <select
                    className="input"
                    value={formData.jenis_tajaan}
                    onChange={(e) => setFormData({ ...formData, jenis_tajaan: e.target.value })}
                  >
                    <option value="IFTAR">Iftar</option>
                    <option value="MOREH">Moreh</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Skop Tajaan <span className="text-red-500">*</span>
                  </label>
                  <select
                    className="input"
                    value={formData.sponsor_coverage}
                    onChange={(e) => setFormData({ ...formData, sponsor_coverage: e.target.value })}
                  >
                    <option value="FULL">Penuh</option>
                    <option value="PARTIAL">Sebahagian</option>
                    <option value="GENERAL">Am</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Status Bayaran
                </label>
                <select
                  className="input"
                  value={formData.payment_status}
                  onChange={(e) => setFormData({ ...formData, payment_status: e.target.value })}
                  disabled={editId && isPaid({ payment_status: formData.payment_status })}
                >
                  <option value="PENDING">Menunggu</option>
                  <option value="PAID">Dibayar</option>
                  <option value="FAILED">Gagal</option>
                </select>
                {editId && isPaid({ payment_status: formData.payment_status }) && (
                  <p className="text-xs text-orange-600 mt-1">
                    Status DIBAYAR adalah muktamad dan tidak boleh diubah.
                  </p>
                )}
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
      ) : sponsorships.length === 0 ? (
        <div className="card text-center py-12">
          <p className="text-gray-500 text-lg">Tiada rekod tajaan dijumpai.</p>
          <p className="text-gray-400 mt-1">Klik &quot;Tambah Tajaan&quot; untuk mencipta rekod pertama.</p>
        </div>
      ) : (
        <>
          <div className="md:hidden space-y-3 mb-4">
            {sponsorships.map((s) => (
              <div key={s.id} className="card p-4">
                <div className="flex items-start justify-between gap-3 mb-2">
                  <div>
                    <p className="font-semibold text-gray-900">{s.nama_sponsor}</p>
                    <p className="text-sm text-gray-600">{s.phone}</p>
                  </div>
                  <span className={`px-2 py-1 text-xs font-semibold rounded-full ${paymentBadge(s.payment_status)}`}>
                    {paymentLabel(s.payment_status)}
                  </span>
                </div>

                <div className="text-sm text-gray-600 space-y-1">
                  <p>Jumlah: <span className="font-medium text-gray-900">{formatCurrency(s.jumlah_tajaan)}</span></p>
                  <p>Jenis/Skop: {s.jenis_tajaan} / {s.sponsor_coverage}</p>
                  <p>Hari Iftar: {getDayLabel(s.iftar_day_id)}</p>
                </div>

                <div className="mt-3 flex flex-wrap gap-2">
                  {!isPaid(s) && (
                    <>
                      <button
                        onClick={() => openEdit(s)}
                        className="btn btn-secondary text-xs py-1 px-3"
                      >
                        Edit
                      </button>
                      <button
                        onClick={() => handleMarkPaid(s.id)}
                        className="text-xs py-1 px-3 rounded bg-green-100 text-green-700 hover:bg-green-200 font-medium"
                      >
                        Tandakan Bayar
                      </button>
                    </>
                  )}
                  <button
                    onClick={() => handleDelete(s.id)}
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
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Nama Sponsor</th>
                <th className="text-left py-3 px-4 font-semibold text-gray-700">No Telefon</th>
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Jumlah</th>
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Jenis / Skop</th>
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Bayaran</th>
                <th className="text-left py-3 px-4 font-semibold text-gray-700">Hari Iftar</th>
                <th className="text-right py-3 px-4 font-semibold text-gray-700">Aksi</th>
              </tr>
            </thead>
            <tbody>
              {sponsorships.map((s) => (
                <tr key={s.id} className="border-b border-gray-100 hover:bg-gray-50">
                  <td className="py-3 px-4 font-medium">{s.nama_sponsor}</td>
                  <td className="py-3 px-4">{s.phone}</td>
                  <td className="py-3 px-4 font-medium">{formatCurrency(s.jumlah_tajaan)}</td>
                  <td className="py-3 px-4 text-gray-600">
                    {s.jenis_tajaan} / {s.sponsor_coverage}
                  </td>
                  <td className="py-3 px-4">
                    <span
                      className={`px-2 py-1 text-xs font-semibold rounded-full ${paymentBadge(s.payment_status)}`}
                    >
                      {paymentLabel(s.payment_status)}
                    </span>
                  </td>
                  <td className="py-3 px-4 text-gray-600">{getDayLabel(s.iftar_day_id)}</td>
                  <td className="py-3 px-4">
                    <div className="flex gap-2 justify-end flex-wrap">
                      {!isPaid(s) && (
                        <>
                          <button
                            onClick={() => openEdit(s)}
                            className="btn btn-secondary text-xs py-1 px-3"
                          >
                            Edit
                          </button>
                          <button
                            onClick={() => handleMarkPaid(s.id)}
                            className="text-xs py-1 px-3 rounded bg-green-100 text-green-700 hover:bg-green-200 font-medium"
                          >
                            Tandakan Bayar
                          </button>
                        </>
                      )}
                      <button
                        onClick={() => handleDelete(s.id)}
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
              Paparan {sponsorships.length} rekod dari {meta.total} jumlah.
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

export default Sponsorships;
