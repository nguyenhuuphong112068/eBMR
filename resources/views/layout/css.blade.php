      <!-- Google Fonts: Inter -->
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
      
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/fontawesome-free/css/all.min.css')}} ">
      <!-- DataTables -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}">
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}">
      <!-- Theme style -->
      <link rel="stylesheet" href="{{asset ('dataTable/dist/css/adminlte.min.css')}}">

      <link rel="stylesheet" href="{{asset ('css/ionicons.min.css')}}">
      <!-- daterange picker -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/daterangepicker/daterangepicker.css')}}">
      <!-- iCheck for checkboxes and radio inputs -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/icheck-bootstrap/icheck-bootstrap.min.css')}}">
      <!-- Bootstrap Color Picker -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css')}}">
      <!-- Tempusdominus Bbootstrap 4 -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')}}">
      <!-- Select2 -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/select2/css/select2.min.css')}}">
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">
      <!-- Bootstrap4 Duallistbox -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css')}}">
      <!-- Toastr -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/toastr/toastr.min.css')}}">
      <!-- SweetAlert2 -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css')}}">
      <!-- Summernote -->
      <link rel="stylesheet" href="{{asset ('dataTable/plugins/summernote/summernote-bs4.min.css')}}">

      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
      
      <style>
            :root {
                  --primary: #0891b2; /* Cyan-600 */
                  --primary-dark: #164e63; /* Cyan-900 */
                  --accent: #22d3ee; /* Cyan-400 */
                  --bg-dark: #164e63;
                  --bg-light: #ecfeff; /* Ultra-light Cyan background */
                  --bg-body: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
                  --text-main: #164e63;
                  --text-muted: #64748b;
                  --glass: rgba(255, 255, 255, 0.8);
                  --glass-border: rgba(8, 145, 178, 0.2);
                  --border-radius-lg: 24px;
                  --border-radius-md: 14px;
                  --shadow-sm: 0 1px 3px rgba(8, 145, 178, 0.1);
                  --shadow-md: 0 10px 15px -3px rgba(8, 145, 178, 0.15);
                  --shadow-lg: 0 25px 50px -12px rgba(8, 145, 178, 0.1);
                  --transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }

            body {
                  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                  background: var(--bg-body);
                  background-attachment: fixed;
                  color: var(--text-main);
                  overflow-x: hidden;
            }

            h1, h2, h3, h4, h5, .brand-text {
                  font-weight: 700;
                  letter-spacing: -0.025em;
                  color: var(--bg-dark);
            }

            .main-sidebar {
                  background-color: var(--bg-dark) !important;
            }

            .nav-sidebar .nav-link.active {
                  background-color: var(--primary) !important;
                  color: #fff !important;
                  box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
            }

            .card {
                  border: 1px solid var(--glass-border);
                  background: var(--glass);
                  backdrop-filter: blur(10px);
                  border-radius: var(--border-radius-lg);
                  box-shadow: var(--shadow-sm);
                  transition: all var(--transition);
            }

            .card:hover {
                  box-shadow: var(--shadow-md);
            }

            .btn-primary {
                  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
                  border: none;
                  border-radius: var(--border-radius-md);
                  padding: 10px 20px;
                  font-weight: 600;
                  box-shadow: 0 4px 6px -1px rgba(14, 165, 233, 0.2);
                  transition: all var(--transition);
            }

            .btn-primary:hover {
                  transform: translateY(-2px);
                  box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.3);
                  filter: brightness(1.1);
            }

            .form-control {
                  border-radius: var(--border-radius-md);
                  border: 2px solid #e2e8f0;
                  padding: 10px 15px;
                  transition: all var(--transition);
            }

            .form-control:focus {
                  border-color: var(--primary);
                  box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
            }

            /* Custom scrollbar */
            ::-webkit-scrollbar {
                  width: 8px;
            }
            ::-webkit-scrollbar-track {
                  background: #f1f5f9;
            }
            ::-webkit-scrollbar-thumb {
                  background: #cbd5e1;
                  border-radius: 4px;
            }
            ::-webkit-scrollbar-thumb:hover {
                  background: #94a3b8;
            }

            body.modal-open {
                  padding: 0 !important;
                  overflow-y: scroll;
            }
      </style>
