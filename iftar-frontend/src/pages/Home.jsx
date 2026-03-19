import { Link } from 'react-router-dom';

const Home = () => {
  return (
    <div className="min-h-screen bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 text-white">
      <div className="max-w-6xl mx-auto px-6 py-16">
        <div className="max-w-3xl">
          <p className="text-primary-100 uppercase tracking-[0.3em] text-xs mb-4">Mini SaaS Iftar Management</p>
          <h1 className="text-5xl font-bold leading-tight mb-6">Satu portal untuk pengurusan event iftar dan pendaftaran peserta.</h1>
          <p className="text-lg text-primary-50/90 max-w-2xl mb-10">
            Frontend kini dipecahkan kepada dua aliran: pendaftaran peserta awam dan panel pengurusan masjid.
          </p>
        </div>

        <div className="grid md:grid-cols-2 gap-6">
          <div className="bg-white/10 backdrop-blur rounded-2xl p-8 border border-white/20">
            <p className="text-sm text-primary-100 mb-3">Untuk pengguna awam</p>
            <h2 className="text-3xl font-semibold mb-4">Daftar Iftar</h2>
            <p className="text-primary-50/90 mb-6">
              Pilih masjid, semak event yang masih dibuka, dan hantar pendaftaran terus dari frontend.
            </p>
            <Link to="/register" className="inline-flex btn bg-white text-primary-800 hover:bg-primary-50">
              Buka Pendaftaran
            </Link>
          </div>

          <div className="bg-black/20 backdrop-blur rounded-2xl p-8 border border-white/10">
            <p className="text-sm text-primary-100 mb-3">Untuk pihak masjid</p>
            <h2 className="text-3xl font-semibold mb-4">Panel Pengurusan</h2>
            <p className="text-primary-50/90 mb-6">
              Akses dashboard, hari iftar, peserta, tajaan, dan profil masjid dalam satu panel operasi.
            </p>
            <Link to="/admin/login" className="inline-flex btn btn-secondary bg-white/90 text-gray-900 hover:bg-white">
              Masuk Panel
            </Link>
          </div>
        </div>

        {/*
        <div className="mt-6 bg-amber-100/90 text-amber-950 border border-amber-200 rounded-2xl p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <p className="text-xs uppercase tracking-[0.2em] font-semibold">Demo Utility</p>
            <p className="text-sm mt-1">Akses halaman superadmin untuk semak dan luluskan akaun admin pending.</p>
          </div>
          <Link to="/superadmin/approvals" className="inline-flex btn bg-amber-900 text-white hover:bg-amber-800">
            Buka Superadmin Approvals
          </Link>
        </div>
        */}
      </div>
    </div>
  );
};

export default Home;