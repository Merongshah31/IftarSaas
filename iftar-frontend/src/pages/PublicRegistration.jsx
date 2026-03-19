import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import Alert from '../components/Alert';
import Loading from '../components/Loading';
import { masjidAPI, participantsAPI } from '../api/client';
import { formatDate, getErrorMessage } from '../utils/helpers';

const EMPTY_FORM = {
  iftar_day_id: '',
  nama: '',
  no_telefon: '',
  bil_pax: 1,
  notes: '',
};

const PublicRegistration = () => {
  const [masjids, setMasjids] = useState([]);
  const [selectedMasjidId, setSelectedMasjidId] = useState('');
  const [iftarDays, setIftarDays] = useState([]);
  const [loadingMasjids, setLoadingMasjids] = useState(true);
  const [loadingDays, setLoadingDays] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [successState, setSuccessState] = useState(null);
  const [formData, setFormData] = useState(EMPTY_FORM);

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

  useEffect(() => {
    // Always reset selected iftar day when masjid changes.
    setFormData((current) => ({ ...current, iftar_day_id: '' }));

    if (!selectedMasjidId) {
      setIftarDays([]);
      return;
    }

    const loadIftarDays = async () => {
      try {
        setLoadingDays(true);
        setError('');
        const response = await masjidAPI.listOpenIftarDaysPublic(selectedMasjidId, { per_page: 100 });
        setIftarDays(response.data || []);
      } catch (err) {
        setError(getErrorMessage(err));
      } finally {
        setLoadingDays(false);
      }
    };

    loadIftarDays();
  }, [selectedMasjidId]);

  const handleSubmit = async (event) => {
    event.preventDefault();

    if (!selectedMasjidId) {
      setError('Sila pilih masjid terlebih dahulu.');
      return;
    }

    try {
      setSubmitting(true);
      setError('');

      const selectedDay = iftarDays.find((day) => String(day.id) === String(formData.iftar_day_id));
      await participantsAPI.registerPublic(selectedMasjidId, {
        ...formData,
        iftar_day_id: parseInt(formData.iftar_day_id, 10),
        bil_pax: parseInt(formData.bil_pax, 10),
      });
      setSuccessState({
        participantName: formData.nama,
        masjidName: selectedMasjid?.nama_masjid || 'Masjid dipilih',
        eventDate: selectedDay ? formatDate(selectedDay.tarikh) : '-',
      });
      setFormData(EMPTY_FORM);
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

  const selectedMasjid = masjids.find((item) => String(item.id) === String(selectedMasjidId));

  if (loadingMasjids) {
    return <Loading />;
  }

  if (successState) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-green-50 to-white flex items-center justify-center px-4 py-8">
        <div className="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 text-center border border-green-100">
          <div className="mx-auto mb-6 relative w-24 h-24">
            <div className="absolute inset-0 rounded-full bg-green-200/70 animate-ping"></div>
            <div className="relative w-24 h-24 rounded-full bg-green-100 border-[8px] border-green-500 flex items-center justify-center">
              <svg className="w-10 h-10 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3">
                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </div>

          <h2 className="text-3xl font-bold text-gray-900 mb-3">Pendaftaran Berjaya!</h2>
          <p className="text-gray-600 leading-relaxed mb-5">
            Terima kasih <span className="font-semibold text-gray-900">{successState.participantName}</span>. 
            Pendaftaran iftar anda telah diterima.
          </p>

          <div className="rounded-xl bg-green-50 border border-green-100 p-4 text-left text-sm text-gray-700 mb-6 space-y-1">
            <p><span className="font-semibold">Masjid:</span> {successState.masjidName}</p>
            <p><span className="font-semibold">Tarikh Iftar:</span> {successState.eventDate}</p>
          </div>

          <div className="space-y-3">
            <button
              type="button"
              className="btn w-full rounded-full bg-green-500 text-white hover:bg-green-600"
              onClick={() => {
                setSuccessState(null);
                setError('');
              }}
            >
              Daftar Lagi
            </button>
            <Link to="/" className="btn w-full rounded-full btn-secondary">
              Kembali ke Utama
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
          <div>
            <p className="text-sm text-primary-700 font-semibold mb-2">Pendaftaran Awam</p>
            <h1 className="text-4xl font-bold text-gray-900">Daftar untuk event iftar</h1>
            <p className="text-gray-600 mt-2 max-w-2xl">
              Pilih masjid dan event yang masih dibuka. Sistem akan semak duplicate, status event, dan kapasiti semasa anda menghantar borang.
            </p>
          </div>
          <div className="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
            <Link to="/" className="btn btn-secondary w-full sm:w-auto text-center">
              Kembali ke Home
            </Link>
            <Link to="/admin/login" className="btn btn-secondary w-full sm:w-auto text-center">
              Buka Panel Masjid
            </Link>
          </div>
        </div>

        {error && <Alert type="error" message={error} onClose={() => setError('')} />}

        <div className="grid lg:grid-cols-[1.1fr_0.9fr] gap-6">
          <div className="card">
            <h2 className="text-xl font-bold mb-5">Borang Pendaftaran</h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="label">Masjid</label>
                <select
                  id="masjid-select"
                  className="input"
                  value={selectedMasjidId}
                  onChange={(e) => setSelectedMasjidId(e.target.value)}
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

              {selectedMasjid && (
                <div className="md:hidden rounded-lg border border-primary-200 bg-primary-50 p-3">
                  <p className="text-xs font-semibold uppercase tracking-[0.12em] text-primary-700 mb-1">Masjid Dipilih</p>
                  <p className="font-semibold text-gray-900">{selectedMasjid.nama_masjid}</p>
                  <p className="text-sm text-gray-600">{selectedMasjid.negeri}</p>
                  {selectedMasjid.contact_phone && (
                    <p className="text-xs text-gray-500 mt-1">Hubungi: {selectedMasjid.contact_phone}</p>
                  )}
                </div>
              )}

              <div>
                <label className="label">Hari Iftar</label>
                <select
                  className="input"
                  value={formData.iftar_day_id}
                  onChange={(e) => setFormData((current) => ({ ...current, iftar_day_id: e.target.value }))}
                  disabled={!selectedMasjidId || loadingDays || iftarDays.length === 0}
                >
                  <option value="">-- Pilih Hari Iftar --</option>
                  {iftarDays.map((day) => (
                    <option key={day.id} value={day.id}>
                      {formatDate(day.tarikh)} - {day.available_slots} slot lagi
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="label">Nama</label>
                  <input
                    className="input"
                    type="text"
                    value={formData.nama}
                    onChange={(e) => setFormData((current) => ({ ...current, nama: e.target.value }))}
                    required
                    maxLength={255}
                  />
                </div>
                <div>
                  <label className="label">No Telefon</label>
                  <input
                    className="input"
                    type="text"
                    value={formData.no_telefon}
                    onChange={(e) => setFormData((current) => ({ ...current, no_telefon: e.target.value }))}
                    required
                    maxLength={15}
                    placeholder="Contoh: 012-3456789"
                    pattern="01[0-9]-[0-9]{7,8}"
                  />
                  <p className="text-xs text-gray-500 mt-1">Contoh format: 012-3456789 atau 017-12345678</p>
                </div>
              </div>

              <div>
                <label className="label">Bilangan Pax</label>
                <input
                  className="input"
                  type="number"
                  min={1}
                  max={10}
                  value={formData.bil_pax}
                  onChange={(e) => setFormData((current) => ({ ...current, bil_pax: e.target.value }))}
                  required
                />
              </div>

              <div>
                <label className="label">Nota</label>
                <textarea
                  className="input"
                  rows={3}
                  maxLength={500}
                  value={formData.notes}
                  onChange={(e) => setFormData((current) => ({ ...current, notes: e.target.value }))}
                  placeholder="Maklumat tambahan jika perlu"
                />
              </div>

              <button
                type="submit"
                className="btn btn-primary w-full"
                disabled={submitting || !selectedMasjidId || !formData.iftar_day_id}
              >
                {submitting ? 'Menghantar...' : 'Hantar Pendaftaran'}
              </button>
            </form>
          </div>

          <div className="space-y-6">
            <div className="card">
              <h2 className="text-xl font-bold mb-3">Masjid Dipilih</h2>
              {selectedMasjid ? (
                <div>
                  <p className="font-semibold text-lg text-gray-900">{selectedMasjid.nama_masjid}</p>
                  <p className="text-gray-600">{selectedMasjid.negeri}</p>
                  {selectedMasjid.contact_phone && (
                    <p className="text-sm text-gray-500 mt-2">Hubungi: {selectedMasjid.contact_phone}</p>
                  )}
                </div>
              ) : (
                <p className="text-gray-500">Belum ada masjid dipilih.</p>
              )}
            </div>

            <div className="card">
              <h2 className="text-xl font-bold mb-3">Event Dibuka</h2>
              {loadingDays ? (
                <Loading size="sm" text="Memuatkan event..." />
              ) : iftarDays.length === 0 ? (
                <p className="text-gray-500">Tiada event iftar dibuka untuk masjid ini pada masa sekarang.</p>
              ) : (
                <div className="space-y-3">
                  {iftarDays.map((day) => (
                    <div key={day.id} className="rounded-lg border border-gray-200 p-4">
                      <p className="font-semibold text-gray-900">{formatDate(day.tarikh)}</p>
                      <p className="text-sm text-gray-600 mt-1">
                        {day.jumlah_daftar} / {day.kapasiti_max} peserta, baki {day.available_slots} slot
                      </p>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PublicRegistration;