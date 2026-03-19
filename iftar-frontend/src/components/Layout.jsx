import { Link, useLocation } from 'react-router-dom';
import { useApp } from '../context/AppContext';

const Layout = ({ children }) => {
  const { adminUser, masjid, logout } = useApp();
  const location = useLocation();

  const navigation = [
    { name: 'Dashboard', path: '/dashboard' },
    { name: 'Hari Iftar', path: '/iftar-days' },
    { name: 'Peserta', path: '/participants' },
    { name: 'Tajaan', path: '/sponsorships' },
    { name: 'Profil Masjid', path: '/profile' },
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <nav className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex">
              <div className="flex-shrink-0 flex items-center">
                <h1 className="text-xl font-bold text-primary-600">
                  🕌 Sistem Iftar
                </h1>
              </div>
              <div className="hidden sm:ml-8 sm:flex sm:space-x-4">
                {navigation.map((item) => (
                  <Link
                    key={item.path}
                    to={item.path}
                    className={`inline-flex items-center px-3 py-2 text-sm font-medium ${
                      location.pathname === item.path
                        ? 'border-b-2 border-primary-500 text-primary-600'
                        : 'text-gray-700 hover:text-primary-600'
                    }`}
                  >
                    {item.name}
                  </Link>
                ))}
              </div>
            </div>
            <div className="flex items-center">
              {(masjid || adminUser) && (
                <div className="hidden sm:block text-sm text-right mr-4">
                  {adminUser && <p className="font-medium text-gray-900">{adminUser.name}</p>}
                  {masjid && <p className="text-gray-500 text-xs">{masjid.nama_masjid}</p>}
                </div>
              )}
              <button
                onClick={logout}
                className="btn btn-secondary text-sm"
              >
                Keluar
              </button>
            </div>
          </div>
        </div>

        {/* Mobile menu */}
        <div className="sm:hidden px-4 py-3 border-t">
          <div className="flex flex-col space-y-1">
            {navigation.map((item) => (
              <Link
                key={item.path}
                to={item.path}
                className={`px-3 py-2 rounded-md text-sm font-medium ${
                  location.pathname === item.path
                    ? 'bg-primary-50 text-primary-600'
                    : 'text-gray-700 hover:bg-gray-50'
                }`}
              >
                {item.name}
              </Link>
            ))}
          </div>
        </div>
      </nav>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {children}
      </main>

      {/* Footer */}
      <footer className="bg-white border-t mt-auto">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <p className="text-center text-sm text-gray-500">
            © 2026 Sistem Pengurusan Iftar. Hak cipta terpelihara.
          </p>
        </div>
      </footer>
    </div>
  );
};

export default Layout;
