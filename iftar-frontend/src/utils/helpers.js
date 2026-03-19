export const validatePhone = (phone) => {
  const phoneRegex = /^01[0-9]-[0-9]{7,8}$/;
  return phoneRegex.test(phone);
};

export const formatPhone = (phone) => {
  // Remove all non-digits
  const digits = phone.replace(/\D/g, '');
  
  // Format as 01X-XXXXXXX
  if (digits.length >= 3) {
    return `${digits.slice(0, 3)}-${digits.slice(3)}`;
  }
  return digits;
};

export const validateDate = (date) => {
  const selectedDate = new Date(date);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  return selectedDate >= today;
};

export const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('ms-MY', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

export const formatDateTime = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleString('ms-MY', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

export const formatCurrency = (amount) => {
  return new Intl.NumberFormat('ms-MY', {
    style: 'currency',
    currency: 'MYR',
  }).format(amount);
};

export const getErrorMessage = (error) => {
  if (typeof error === 'string') {
    return error;
  }
  
  if (error.message) {
    return error.message;
  }
  
  if (error.errors && typeof error.errors === 'object') {
    const firstError = Object.values(error.errors)[0];
    return Array.isArray(firstError) ? firstError[0] : firstError;
  }
  
  return 'Ralat tidak dijangka berlaku';
};

export const getStatusBadgeClass = (status) => {
  const normalizedStatus = String(status || '').toUpperCase();

  const statusClasses = {
    OPEN: 'bg-green-100 text-green-800',
    FULL: 'bg-yellow-100 text-yellow-800',
    CLOSED: 'bg-red-100 text-red-800',
    PENDING: 'bg-blue-100 text-blue-800',
    CHECKED_IN: 'bg-green-100 text-green-800',
    NO_SHOW: 'bg-gray-100 text-gray-800',
    PAID: 'bg-green-100 text-green-800',
    UNPAID: 'bg-red-100 text-red-800',
  };
  
  return statusClasses[normalizedStatus] || 'bg-gray-100 text-gray-800';
};

export const getStatusLabel = (status) => {
  const normalizedStatus = String(status || '').toUpperCase();

  const statusLabels = {
    OPEN: 'Dibuka',
    FULL: 'Penuh',
    CLOSED: 'Ditutup',
    PENDING: 'Menunggu',
    CHECKED_IN: 'Hadir',
    NO_SHOW: 'Tidak Hadir',
    PAID: 'Dibayar',
    UNPAID: 'Belum Dibayar',
    IFTAR: 'Iftar',
    MOREH: 'Moreh',
    FULL: 'Penuh',
    PARTIAL: 'Sebahagian',
    GENERAL: 'Am',
  };
  
  return statusLabels[normalizedStatus] || status;
};
