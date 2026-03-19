import { getStatusBadgeClass, getStatusLabel } from '../utils/helpers';

const StatusBadge = ({ status }) => {
  return (
    <span className={`px-3 py-1 text-xs font-semibold rounded-full ${getStatusBadgeClass(status)}`}>
      {getStatusLabel(status)}
    </span>
  );
};

export default StatusBadge;
