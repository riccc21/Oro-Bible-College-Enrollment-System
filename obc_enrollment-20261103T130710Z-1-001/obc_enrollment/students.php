<?php
session_start();
if (!isset($_SESSION['registrar'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "obc_enrollment");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Check if viewing archived students
$view_archived = isset($_GET['view']) && $_GET['view'] === 'archived';
$status_filter = $view_archived ? 'Archived' : 'Active';

// Archive student action
if (isset($_POST['archive_student'])) {
    $id = intval($_POST['archive_id']);
    $conn->query("UPDATE enrollments SET status='Archived' WHERE student_id = $id");
    header("Location: students.php");
    exit();
}

// Restore student action
if (isset($_GET['restore_id'])) {
    $id = intval($_GET['restore_id']);
    $conn->query("UPDATE enrollments SET status='Active' WHERE student_id = $id");
    header("Location: students.php?view=archived");
    exit();
}

// Updated Query to fetch section details
$sql = "SELECT e.*, s.section_name, s.grade_level as section_grade 
        FROM enrollments e 
        LEFT JOIN sections s ON e.section_id = s.section_id 
        WHERE e.status='$status_filter' 
        ORDER BY e.last_name ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Students</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="dashboard.css">
<style>
/* Keeping all your original styles */
body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f8fafc; }
.main-content { padding: 30px; transition: opacity 0.3s ease; }
.sidebar { font-family: 'Times New Roman', Times, serif; font-size: 1.3rem; }
.sidebar ul li a.logout-btn { color: #dc2626 !important; font-weight: bold; }
.table-container { background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden; margin-top: 20px; }
table { width: 100%; border-collapse: collapse; }
th { background: #1e3a8a; color: #fff; padding: 15px; font-size: 13px; text-transform: uppercase; text-align: left; }
td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
button { cursor: pointer; border-radius: 6px; border: none; color: #fff; font-weight: 600; padding: 8px 12px; margin-right: 5px; transition: 0.2s; }
button:hover { opacity: 0.9; transform: translateY(-1px); }
.edit-btn { background: #1e3a8a; }
button.guardian-btn { background: #10b981; }
button.viewmore-btn { background: #3b82f6; }
button.archive-btn { background: #ef4444; }
.search-container { position: relative; margin-bottom: 20px; }
.search-container i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #888; }
#searchInput { padding: 10px 15px 10px 40px; border-radius: 8px; border: 1px solid #ddd; width: 300px; }
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
.modal-content { background: #fff; padding: 25px; border-radius: 12px; width: 90%; max-width: 500px; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.modal-body .data-row { display: flex; justify-content: space-between; margin-bottom: 12px; }
.data-label { color: #64748b; font-weight: 600; }
.data-value { color: #1e293b; font-weight: 500; }
.close-btn { font-size: 24px; cursor: pointer; }
.close-btn:hover { color: #ef4444; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px; }
.btn-cancel { background: #e2e8f0; color: #475569; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
.btn-confirm { background: #ef4444; color: #fff; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
</style>
</head>
<body>

<div class="dashboard-container">
    <div class="sidebar">
        <div class="logo-container"><img src="Oro_Bible_Logo.png" alt="Logo"></div>
        <h2>Registrar</h2>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="students.php" class="active"><i class="fas fa-user-graduate"></i> Students</a></li>
            <li><a href="sections.php"><i class="fas fa-layer-group"></i> Sections</a></li>
            <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar" style="display:flex; justify-content:space-between; align-items:center;">
            <h1><?php echo $view_archived ? "Archived Students" : "Students"; ?></h1>
            <div>
                <?php if (!$view_archived): ?>
                    <a href="?view=archived" style="background:#64748b;color:white;padding:8px 15px;border-radius:6px;text-decoration:none;">
                        <i class="fas fa-box-archive"></i> View Archive
                    </a>
                <?php else: ?>
                    <a href="students.php" style="background:#1e3a8a;color:white;padding:8px 15px;border-radius:6px;text-decoration:none;">
                        <i class="fas fa-arrow-left"></i> Back to Active
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search students..." onkeyup="filterTable()">
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Status</th>
                        <th>Grade & Section</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="studentsTable">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()):
                            $fullName = htmlspecialchars($row['last_name'] . ", " . $row['first_name'] . " " . $row['middle_initial'] . " " . $row['suffix']);
                            $statusColor = ($row['status'] === 'Active') ? '#dcfce7' : '#fee2e2';
                            $textColor = ($row['status'] === 'Active') ? '#166534' : '#991b1b';
                            
                            // Grade & Section format
                            $displaySection = "Unassigned";
                            if (!empty($row['section_name'])) {
                                $displaySection = "Grade " . $row['section_grade'] . " - " . htmlspecialchars($row['section_name']);
                            }

                            $guardianInfo = htmlspecialchars(json_encode([
                                'Name'=>$row['guardian_name'] ?? 'N/A',
                                'DOB'=>$row['guardian_dob'] ?? 'N/A',
                                'Contact'=>$row['guardian_contact'] ?? 'N/A',
                                'Address'=>$row['guardian_address'] ?? 'N/A'
                            ]));
                            
                            // Updated this specific array to include Grade Level
                            $viewMoreInfo = htmlspecialchars(json_encode([
                                'Grade'=>$row['grade_level'] ?? 'N/A',
                                'Contact'=>$row['contact'] ?? 'N/A',
                                'Age'=>$row['age'] ?? 'N/A',
                                'Gender'=>$row['gender'] ?? 'N/A',
                                'DOB'=>$row['dob'] ?? 'N/A',
                                'Address'=>$row['address'] ?? 'N/A',
                                'City'=>$row['city'] ?? 'N/A'
                            ]));
                        ?>
                        <tr>
                            <td><?php echo $row['student_id']; ?></td>
                            <td><strong><?php echo $fullName; ?></strong></td>
                            <td>
                                <span style="background:<?php echo $statusColor; ?>;color:<?php echo $textColor; ?>;padding:4px 10px;border-radius:15px;font-size:0.75rem;font-weight:bold;">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $displaySection; ?></td>
                            <td>
                                <button class="guardian-btn" onclick='showGuardianModal(<?php echo $guardianInfo; ?>)'>Guardian Info</button>
                                <button class="viewmore-btn" onclick='showViewMoreModal(<?php echo $viewMoreInfo; ?>)'>View More</button>
                                <?php if (!$view_archived): ?>
                                    <button class="archive-btn" onclick="openArchiveModal(<?php echo $row['student_id']; ?>,'<?php echo addslashes($fullName); ?>')">Archive</button>
                                <?php else: ?>
                                    <a href="?restore_id=<?php echo $row['student_id']; ?>" style="background:#3b82f6;color:white;padding:5px 10px;border-radius:6px;text-decoration:none;">Restore</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No students found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="guardianModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Guardian Info</h2>
            <span class="close-btn" onclick="closeModal('guardianModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="data-row"><span class="data-label">Full Name</span><span class="data-value" id="gName"></span></div>
            <div class="data-row"><span class="data-label">Contact</span><span class="data-value" id="gContact"></span></div>
            <div class="data-row"><span class="data-label">Address</span><span class="data-value" id="gAddress"></span></div>
        </div>
    </div>
</div>

<div id="viewMoreModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Student Info</h2>
            <span class="close-btn" onclick="closeModal('viewMoreModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="data-row"><span class="data-label">Grade Level</span><span class="data-value" id="vGrade"></span></div>
            <div class="data-row"><span class="data-label">Contact</span><span class="data-value" id="vContact"></span></div>
            <div class="data-row"><span class="data-label">Age</span><span class="data-value" id="vAge"></span></div>
            <div class="data-row"><span class="data-label">Gender</span><span class="data-value" id="vGender"></span></div>
            <div class="data-row"><span class="data-label">Date of Birth</span><span class="data-value" id="vDOB"></span></div>
            <div class="data-row"><span class="data-label">Address</span><span class="data-value" id="vAddress"></span></div>
            <div class="data-row"><span class="data-label">City</span><span class="data-value" id="vCity"></span></div>
        </div>
    </div>
</div>

<div id="archiveModal" class="modal">
    <div class="modal-content">
        <h3>Archive Student?</h3>
        <p>Move <span id="archiveStudentName" style="font-weight:bold;"></span> to Archive?</p>
        <form method="POST">
            <input type="hidden" name="archive_id" id="archive_id">
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('archiveModal')">Cancel</button>
                <button type="submit" name="archive_student" class="btn-confirm">Yes, Archive</button>
            </div>
        </form>
    </div>
</div>

<script>
function showGuardianModal(info){
    document.getElementById('gName').innerText = info.Name;
    document.getElementById('gContact').innerText = info.Contact;
    document.getElementById('gAddress').innerText = info.Address;
    document.getElementById('guardianModal').style.display = 'flex';
}

function showViewMoreModal(info){
    // Added this specific line to populate Grade Level
    document.getElementById('vGrade').innerText = info.Grade;
    
    document.getElementById('vContact').innerText = info.Contact;
    document.getElementById('vAge').innerText = info.Age;
    document.getElementById('vGender').innerText = info.Gender;
    document.getElementById('vDOB').innerText = info.DOB;
    document.getElementById('vAddress').innerText = info.Address;
    document.getElementById('vCity').innerText = info.City;
    document.getElementById('viewMoreModal').style.display = 'flex';
}

function openArchiveModal(id, name){
    document.getElementById('archive_id').value = id;
    document.getElementById('archiveStudentName').innerText = name;
    document.getElementById('archiveModal').style.display = 'flex';
}

function closeModal(id){
    document.getElementById(id).style.display = 'none';
}

window.onclick = function(event){
    const modals = ['guardianModal', 'viewMoreModal', 'archiveModal'];
    modals.forEach(id => {
        const m = document.getElementById(id);
        if (event.target == m) m.style.display = 'none';
    });
}

function filterTable() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toUpperCase();
    const table = document.getElementById("studentsTable");
    const tr = table.getElementsByTagName("tr");
    for (let i = 0; i < tr.length; i++) {
        const tds = tr[i].getElementsByTagName("td");
        if (tds.length > 0) {
            let combined = '';
            for (let j = 0; j < tds.length; j++) { combined += tds[j].textContent.toUpperCase() + ' '; }
            tr[i].style.display = combined.indexOf(filter) > -1 ? "" : "none";
        }
    }
}
</script>

</body>
</html>
<?php $conn->close(); ?>