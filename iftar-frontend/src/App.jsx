import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AppProvider, useApp } from './context/AppContext';
import Layout from './components/Layout';
import Loading from './components/Loading';
import Home from './pages/Home';
import Dashboard from './pages/Dashboard';
import IftarDays from './pages/IftarDays';
import Participants from './pages/Participants';
import Sponsorships from './pages/Sponsorships';
import Profile from './pages/Profile';
import PublicRegistration from './pages/PublicRegistration';
import AdminLogin from './pages/AdminLogin';
import AdminRegister from './pages/AdminRegister';
import SuperadminApprovals from './pages/SuperadminApprovals';

const DemoHackathonNotice = () => {
  return (
    <div className="bg-amber-100 border-b border-amber-300 text-amber-900">
      <div className="max-w-7xl mx-auto px-4 py-3 text-sm font-medium flex items-start gap-3">
        <span className="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-200 text-amber-800 font-bold">!</span>
        <p>
          Notis: Platform ini adalah versi <strong>Demo / Hackathon Phase</strong>. Data, aliran proses,
          dan prestasi mungkin berubah sebelum release production.
        </p>
      </div>
    </div>
  );
};

// Protected Route Component
const ProtectedRoute = ({ children }) => {
  const { adminUser, masjid, loading } = useApp();

  if (loading) {
    return <Loading />;
  }

  if (!adminUser || !masjid) {
    return <Navigate to="/admin/login" replace />;
  }

  return <Layout>{children}</Layout>;
};

// Public Route Component (redirect if already logged in)
const PublicRoute = ({ children }) => {
  const { adminUser, loading } = useApp();

  if (loading) {
    return <Loading />;
  }

  if (adminUser) {
    return <Navigate to="/dashboard" replace />;
  }

  return children;
};

function AppRoutes() {
  return (
    <BrowserRouter>
      <div className="min-h-screen">
        <DemoHackathonNotice />

        <Routes>
          {/* Public Routes */}
          <Route
            path="/"
            element={
              <PublicRoute>
                <Home />
              </PublicRoute>
            }
          />
          <Route
            path="/register"
            element={
              <PublicRoute>
                <PublicRegistration />
              </PublicRoute>
            }
          />
          <Route
            path="/admin/login"
            element={
              <PublicRoute>
                <AdminLogin />
              </PublicRoute>
            }
          />
          <Route
            path="/admin/register"
            element={
              <PublicRoute>
                <AdminRegister />
              </PublicRoute>
            }
          />
          <Route path="/superadmin/approvals" element={<SuperadminApprovals />} />

          <Route path="/masjid-selection" element={<Navigate to="/admin/login" replace />} />

          {/* Protected Routes */}
          <Route
            path="/dashboard"
            element={
              <ProtectedRoute>
                <Dashboard />
              </ProtectedRoute>
            }
          />
          <Route
            path="/iftar-days"
            element={
              <ProtectedRoute>
                <IftarDays />
              </ProtectedRoute>
            }
          />
          <Route
            path="/participants"
            element={
              <ProtectedRoute>
                <Participants />
              </ProtectedRoute>
            }
          />
          <Route
            path="/sponsorships"
            element={
              <ProtectedRoute>
                <Sponsorships />
              </ProtectedRoute>
            }
          />
          <Route
            path="/profile"
            element={
              <ProtectedRoute>
                <Profile />
              </ProtectedRoute>
            }
          />

          {/* Catch all - redirect to home */}
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </div>
    </BrowserRouter>
  );
}

function App() {
  return (
    <AppProvider>
      <AppRoutes />
    </AppProvider>
  );
}

export default App;
