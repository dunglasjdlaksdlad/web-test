// const FormError = ({ error }: { error?: string }) => {
//   if (!error) return;

//   return <div className="text-red-500 text-sm">{error}</div>;
// };

const FormError = ({ error }: { error?: any }) => {
  if (!error) return null;
  return <span className="text-red-600">{error}</span>;
};

export default FormError;

// const FormError = ({ error }: { error?: string | string[] }) => {
//   if (!error) return null;

//   // Nếu lỗi là mảng, hiển thị tất cả các lỗi
//   if (Array.isArray(error)) {
//     return (
//       <div className="text-red-500 text-sm">
//         {error.map((err, index) => (
//           <div key={index}>{err}</div>
//         ))}
//       </div>
//     );
//   }

//   // Nếu lỗi là chuỗi đơn, hiển thị nó
//   return <div className="text-red-500 text-sm">{error}</div>;
// };

// export default FormError;
