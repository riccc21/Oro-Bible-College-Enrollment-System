<?php
session_start();

if (!isset($_SESSION['registrar'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "obc_enrollment");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// --- PHP ACTIONS ---

// Handle Create Section form submission
if(isset($_POST['create_section'])){
    $section_name = $conn->real_escape_string($_POST['section_name']);
    $grade_level = intval($_POST['grade_level']);
    $adviser = $conn->real_escape_string($_POST['adviser']);

    if($section_name && $grade_level && $adviser){
        $sql = "INSERT INTO sections (section_name, grade_level, adviser, status) VALUES ('$section_name', $grade_level, '$adviser', 'Active')";
        if($conn->query($sql)){
            $_SESSION['success'] = "Section '$section_name' created successfully!";
        } else {
            $_SESSION['error'] = "Error creating section: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "All fields are required!";
    }

    header("Location: sections.php");
    exit();
}

// Handle professional AJAX fetch for students
if(isset($_GET['fetch_students'])){
    $section_id = intval($_GET['fetch_students']);

    $sql = "SELECT first_name, last_name, student_id 
            FROM enrollments 
            WHERE section_id = $section_id 
            AND status != 'Rejected'
            ORDER BY last_name ASC";

    $res = $conn->query($sql);

    echo "<div class='student-list-container'>";
    if($res && $res->num_rows > 0){
        while($row = $res->fetch_assoc()){
            $sId = $row['student_id'];
            $fullName = htmlspecialchars($row['last_name'] . ", " . $row['first_name']);
            
            echo "
            <div class='student-row' data-name='".strtolower($fullName)."'>
                <div class='student-info'>
                    <div class='student-avatar'><i class='fas fa-user'></i></div>
                    <div>
                        <div class='student-name'>$fullName</div>
                        <div class='student-subtext'>ID: $sId</div>
                    </div>
                </div>
                <button class='remove-btn' onclick='removeStudentFromSection(\"$sId\", $section_id)'>
                    <i class='fas fa-user-minus'></i> Archive
                </button>
            </div>";
        }
    } else {
        echo "<div style='text-align:center; padding:40px; color:#94a3b8;'>
                <i class='fas fa-users-slash' style='font-size:3rem; margin-bottom:15px; display:block;'></i>
                <p>No students assigned to this section yet.</p>
              </div>";
    }
    echo "</div>";
    exit();
}

// Archive Section
if (isset($_POST['confirm_archive'])) {
    $id = intval($_POST['archive_id']);
    if($conn->query("UPDATE sections SET status = 'Archived' WHERE section_id = $id")){
        $_SESSION['success'] = "Section archived successfully!";
    } else {
        $_SESSION['error'] = "Failed to archive section.";
    }
    header("Location: sections.php");
    exit();
}

// Restore Section
if (isset($_GET['restore_id'])) {
    $id = intval($_GET['restore_id']);
    if($conn->query("UPDATE sections SET status = 'Active' WHERE section_id = $id")){
        $_SESSION['success'] = "Section restored successfully!";
    } else {
        $_SESSION['error'] = "Failed to restore section.";
    }
    header("Location: sections.php?view=archived");
    exit();
}

// Load sections
$view_archived = isset($_GET['view']) && $_GET['view'] == 'archived';
$status_filter = $view_archived ? 'Archived' : 'Active';

$sections = [];
$result = $conn->query("SELECT * FROM sections WHERE status = '$status_filter' ORDER BY section_name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) { 
        $sections[] = $row; 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sections Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="dashboard.css">
<style>
body { font-family: 'Segoe UI', sans-serif; background-color: #f8fafc; margin: 0; }
.animate-slow { animation: fadeIn 0.8s ease-out; transition: opacity 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.sidebar { font-family: 'Times New Roman', Times, serif; font-size: 1.3rem;}
.sidebar ul li a i { margin-right: 10px; width: 20px; text-align: center; }
.sidebar ul li a.logout-btn { color: #dc2626 !important; font-weight: bold; transition: 0.3s; }
.sidebar ul li a.logout-btn:hover { background-color: #fee2e2 !important; color: #991b1b !important; }

.main-content { padding: 40px; }
.top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.search-container { position: relative; margin-bottom: 20px; width: 100%; max-width: 400px; }
.search-container i { position: absolute; left: 15px; top: 12px; color: #94a3b8; }
.search-input { width: 100%; padding: 10px 10px 10px 40px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; transition: 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
.search-input:focus { border-color: #1e3a8a; box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1); }

.table-container { background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; }
table { width: 100%; border-collapse: collapse; }
th { background: #1e3a8a; color: white; padding: 15px; text-align: left; font-size: 13px; text-transform: uppercase; }
td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }

.btn-create { background: #1e3a8a; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
.btn-archive-view { background: #64748b; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; margin-left: 10px; }

.action-cell { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; } 
.btn-view, .btn-archive-act, .btn-create { display:inline-flex; align-items:center; gap:6px; font-weight:600; cursor:pointer; border-radius:4px; border:1px solid transparent; text-decoration:none; padding:5px 12px; }
.btn-view { color:#10b981; border:1px solid #10b981; background:none; }
.btn-view:hover { background-color:#f0fdf4; }
.btn-archive-act { color:#ef4444; border:1px solid #ef4444; background:none; }
.btn-archive-act:hover { background-color:#fef2f2; }
.btn-create { color:white; background:#1e3a8a; }
.btn-create:hover { background:#1b347d; }

/* PROFESSIONAL MODAL STYLES */
.modal { display:none; position:fixed; z-index:2000; left:0; top:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); justify-content:center; align-items:center; backdrop-filter: blur(4px); }
.modal-content { background:white; padding:0; border-radius:12px; width:100%; max-width:600px; text-align:left; max-height:90vh; overflow:hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
.modal-xl { max-width: 800px; }
.modal-header { padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #fff; }
.modal-body { padding: 20px 25px; overflow-y: auto; max-height: calc(90vh - 140px); }
.modal-footer { padding: 15px 25px; border-top: 1px solid #e2e8f0; display:flex; gap:10px; justify-content:flex-end; background: #f8fafc; }

/* Student Row Styling */
.student-list-container { display: flex; flex-direction: column; gap: 10px; }
.student-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; transition: 0.2s; }
.student-row:hover { border-color: #3b82f6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.student-info { display: flex; align-items: center; gap: 12px; }
.student-avatar { width: 38px; height: 38px; background: #eff6ff; color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
.student-name { font-weight: 600; color: #1e293b; font-size: 14px; }
.student-subtext { font-size: 12px; color: #64748b; }

.remove-btn { padding: 7px 12px; color: #ef4444; background: #fff1f2; border: 1px solid #fecaca; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; }
.remove-btn:hover { background: #ef4444; color: white; border-color: #ef4444; }

.btn-cancel { background:white; color:#475569; border:1px solid #e2e8f0; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600; }
.btn-confirm { background:#1e3a8a; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600; }
.btn-danger { background:#ef4444; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600; }

#gradeStudentsList { max-height:350px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; background:#fff; margin-top:15px; }

.modal-search-wrapper { position: relative; margin-bottom: 15px; }
.modal-search-wrapper i { position: absolute; left: 12px; top: 11px; color: #94a3b8; }
.modal-search-input { width: 100%; padding: 8px 10px 8px 35px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; outline: none; box-sizing: border-box; }
</style>
</head>
<body>
<div class="dashboard-container">
<div class="sidebar">
    <div class="logo-container"><img src="Oro_Bible_Logo.png" alt="Logo"></div>
    <h2>Registrar</h2>
    <ul>
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
        <li><a href="sections.php" class="active"><i class="fas fa-layer-group"></i> Sections</a></li>
        <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content animate-slow">
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="top-bar">
        <h1><?php echo $view_archived ? "Archived Sections" : "Sections"; ?></h1>
        <div>
            <?php if (!$view_archived): ?>
                <button class="btn-create" onclick="openModal('sectionModal')"><i class="fas fa-plus-circle"></i> Create Section</button>
                <a href="?view=archived" class="btn-archive-view"><i class="fas fa-box-archive"></i> View Archive Bin</a>
            <?php else: ?>
                <a href="sections.php" class="btn-create"><i class="fas fa-arrow-left"></i> Back to Active</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" id="sectionSearch" class="search-input" onkeyup="searchSections()" placeholder="Search sections...">
    </div>

    <div class="table-container">
        <table id="sectionTable">
            <thead>
                <tr>
                    <th>Section Name</th>
                    <th>Grade Level</th>
                    <th>Adviser</th>
                    <th>Total Students</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sections as $section): 
                    $sId = $section['section_id'];
                    $countQuery = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE section_id = $sId");
                    $count = 0;
                    if($countQuery){
                        $countRow = $countQuery->fetch_assoc();
                        $count = $countRow['c'];
                    }
                ?>
                <tr id="sectionRow<?php echo $sId; ?>">
                    <td><strong><?php echo htmlspecialchars($section['section_name']); ?></strong></td>
                    <td>Grade <?php echo htmlspecialchars($section['grade_level']); ?></td>
                    <td><?php echo htmlspecialchars($section['adviser']); ?></td>
                    <td id="totalStudents<?php echo $sId; ?>"><i class="fas fa-users" style="color:#64748b; font-size: 0.8rem;"></i> <?php echo $count; ?></td>
                    <td class="action-cell">
                    <?php if (!$view_archived): ?>
                        <button class="btn-view" onclick="openStudentsModal(<?php echo $sId; ?>)">
                            <i class="fas fa-eye"></i> View Students
                        </button>
                        <button class="btn-create" onclick="openAddStudentModal(<?php echo $sId; ?>)">
                            <i class="fas fa-user-plus"></i> Add Student
                        </button>
                        <button class="btn-archive-act" onclick="openArchiveModal(<?php echo $sId; ?>, '<?php echo addslashes($section['section_name']); ?>')">
                            <i class="fas fa-archive"></i> Archive
                        </button>
                    <?php else: ?>
                        <a href="?restore_id=<?php echo $sId; ?>" class="btn-view" style="color:#3b82f6; border-color:#3b82f6; text-decoration:none;">
                            <i class="fas fa-undo"></i> Restore
                        </a>
                    <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($sections)): ?>
                <tr>
                    <td colspan="5" style="text-align:center; color:#64748b; padding:25px;">No sections found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<div id="sectionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="margin:0;">Create New Section</h2>
            <span style="font-size:24px; cursor:pointer;" onclick="closeModal('sectionModal')">&times;</span>
        </div>
        <form method="POST" action="sections.php">
            <div class="modal-body">
                <div style="margin-bottom:15px;">
                    <label style="font-weight:600; display:block; margin-bottom:5px;">Section Name</label>
                    <input type="text" name="section_name" class="modal-search-input" style="padding-left:10px;" required>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="font-weight:600; display:block; margin-bottom:5px;">Grade Level</label>
                    <select name="grade_level" class="modal-search-input" style="padding-left:10px;" required>
                        <option value="">Select Grade</option>
                        <?php for($g=1;$g<=10;$g++): ?>
                            <option value="<?php echo $g; ?>">Grade <?php echo $g; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div style="margin-bottom:5px;">
                    <label style="font-weight:600; display:block; margin-bottom:5px;">Adviser</label>
                    <input type="text" name="adviser" class="modal-search-input" style="padding-left:10px;" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('sectionModal')">Cancel</button>
                <button type="submit" name="create_section" class="btn-confirm">Save Section</button>
            </div>
        </form>
    </div>
</div>

<div id="addStudentModal" class="modal">
    <div class="modal-content modal-xl">
        <div class="modal-header">
            <h2 style="margin:0;"><i class="fas fa-user-plus" style="color:#1e3a8a; margin-right:10px;"></i> Assign Students</h2>
            <span style="font-size:24px; cursor:pointer;" onclick="closeModal('addStudentModal')">&times;</span>
        </div>
        <div class="modal-body">
            <input type="hidden" id="addStudentSectionId">
            <div style="margin-bottom:15px;">
                <label style="font-weight:600; display:block; margin-bottom:8px;">Filter by Grade Level</label>
                <select id="gradeSelect" class="modal-search-input" style="padding-left:10px;" onchange="loadGradeStudents()">
                    <option value="">Select Grade</option>
                    <?php for($g=1; $g<=10; $g++): ?>
                        <option value="<?php echo $g; ?>">Grade <?php echo $g; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div id="gradeStudentsList" style="padding:15px; color:#64748b; text-align:center;">Select a grade to see unassigned students.</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeModal('addStudentModal')">Close</button>
        </div>
    </div>
</div>

<div id="studentsModal" class="modal">
    <div class="modal-content modal-xl">
        <div class="modal-header">
            <h2 style="margin:0;"><i class="fas fa-users" style="color:#10b981; margin-right:10px;"></i> Section</h2>
            <span style="font-size:24px; cursor:pointer;" onclick="closeModal('studentsModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="modal-search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="rosterSearch" class="modal-search-input" onkeyup="filterRoster()" placeholder="Search names in this Section...">
            </div>
            <div id="studentsList">Loading roster...</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeModal('studentsModal')">Close</button>
        </div>
    </div>
</div>

<div id="archiveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="margin:0; color:#ef4444;">Archive Section</h2>
            <span style="font-size:24px; cursor:pointer;" onclick="closeModal('archiveModal')">&times;</span>
        </div>
        <div class="modal-body">
            <p style="font-size:15px; color:#475569; margin-bottom:15px;">Are you sure you want to archive this section? Students will remain in the database but the section will be moved to the archive bin.</p>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:15px; border-radius:8px;">
                <strong id="archiveSectionName" style="color:#1e293b; font-size:1.1rem;">Section Name</strong>
            </div>
        </div>
        <form method="POST" action="sections.php">
            <input type="hidden" name="archive_id" id="archiveSectionId">
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('archiveModal')">Cancel</button>
                <button type="submit" name="confirm_archive" class="btn-danger">Confirm Archive</button>
            </div>
        </form>
    </div>
</div>

<script>
// Section Search
function searchSections() {
    let input = document.getElementById("sectionSearch");
    let filter = input.value.toLowerCase();
    let table = document.getElementById("sectionTable");
    let tr = table.getElementsByTagName("tr");
    for (let i = 1; i < tr.length; i++) {
        tr[i].style.display = tr[i].textContent.toLowerCase().includes(filter) ? "" : "none";
    }
}

// Inner Modal Roster Filter
function filterRoster() {
    let filter = document.getElementById("rosterSearch").value.toLowerCase();
    let rows = document.querySelectorAll(".student-row");
    rows.forEach(row => {
        let name = row.getAttribute("data-name");
        row.style.display = name.includes(filter) ? "flex" : "none";
    });
}

// Modals
function openModal(id){ 
    document.getElementById(id).style.display = "flex"; 
}
function closeModal(id){ 
    document.getElementById(id).style.display = "none"; 
}

// Open Students Modal
function openStudentsModal(sectionId){
    let listDiv = document.getElementById('studentsList');
    listDiv.innerHTML = '<div style="text-align:center; padding:30px; color:#64748b;"><i class="fas fa-spinner fa-spin"></i> Loading roster...</div>';
    openModal('studentsModal');

    fetch('sections.php?fetch_students=' + sectionId)
    .then(res => res.text())
    .then(data => {
        listDiv.innerHTML = data;
        document.getElementById('rosterSearch').value = ""; // Clear search on open
    })
    .catch(err => {
        listDiv.innerHTML = 'Error loading roster.';
    });
}

// Add Students
function openAddStudentModal(sectionId){
    document.getElementById('addStudentSectionId').value = sectionId;
    document.getElementById('gradeSelect').value = "";
    document.getElementById('gradeStudentsList').innerHTML = "Select a grade to see unassigned students.";
    openModal('addStudentModal');
}

// Load students by grade (Professional design for unassigned list)
function loadGradeStudents(){
    let grade = document.getElementById('gradeSelect').value;
    let sectionId = document.getElementById('addStudentSectionId').value;
    let listDiv = document.getElementById('gradeStudentsList');

    if(!grade) { 
        listDiv.innerHTML = "Select a grade to see unassigned students."; 
        return; 
    }

    listDiv.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin"></i> Fetching records...</div>';

    fetch('get_unassigned_students.php?grade=' + encodeURIComponent(grade))
    .then(res => res.json())
    .then(data => {
        if(data.length === 0){ 
            listDiv.innerHTML = '<div style="text-align:center; padding:20px; color:#94a3b8;">No unassigned students in this grade.</div>'; 
            return; 
        }

        listDiv.innerHTML = '';
        data.forEach(student => {
            let row = document.createElement('div');
            row.className = 'student-row';
            row.style.margin = '5px 10px';
            row.innerHTML = `
                <div class="student-info">
                    <div class="student-avatar"><i class="fas fa-user-plus"></i></div>
                    <div>
                        <div class="student-name">${student.last_name}, ${student.first_name}</div>
                        <div class="student-subtext">Student ID: ${student.id}</div>
                    </div>
                </div>
                <button class="btn-view" style="font-size:12px; padding:5px 10px;" onclick="confirmAssign('${student.id}', '${student.last_name}', '${sectionId}', this)">
                    Add to Section
                </button>
            `;
            listDiv.appendChild(row);
        });
    })
    .catch(err => { 
        listDiv.innerHTML = "Error loading students."; 
    });
}

function confirmAssign(studId, name, sectId, btnEl) {
    if(confirm('Assign ' + name + ' to this section?')){
        assignStudentToSection(studId, sectId, btnEl.closest('.student-row'));
    }
}

// Assign student
function assignStudentToSection(studentId, sectionId, rowElement){
    fetch('assigned_student.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'student_id=' + encodeURIComponent(studentId) + '&section_id=' + encodeURIComponent(sectionId)
    })
    .then(res => res.text())
    .then(msg => {
        if(msg.trim() === 'success'){
            rowElement.style.opacity = '0';
            setTimeout(() => rowElement.remove(), 300);

            // Update Count
            let totalEl = document.getElementById('totalStudents' + sectionId);
            let count = parseInt(totalEl.innerText.replace(/\D/g,'')) || 0;
            totalEl.innerHTML = '<i class="fas fa-users" style="color:#64748b; font-size:0.8rem;"></i> ' + (count + 1);

        } else {
            alert('Error: ' + msg);
        }
    });
}

// Remove student
function removeStudentFromSection(studentId, sectionId) {
    if(confirm("Are you sure you want to remove this student from the section?")) {
        fetch('remove_student.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'student_id=' + encodeURIComponent(studentId)
        })
        .then(res => res.text())
        .then(msg => {
            if(msg.trim() === 'success') {
                openStudentsModal(sectionId); // Refresh roster
                let countEl = document.getElementById('totalStudents' + sectionId);
                let currentCount = parseInt(countEl.innerText.replace(/\D/g,'')) || 0;
                countEl.innerHTML = '<i class="fas fa-users" style="color:#64748b; font-size:0.8rem;"></i> ' + (currentCount - 1);
            } else {
                alert("Server said: " + msg);
            }
        });
    }
}

// Open Archive Modal
function openArchiveModal(sectionId, sectionName){
    document.getElementById('archiveSectionId').value = sectionId;
    document.getElementById('archiveSectionName').innerText = sectionName;
    openModal('archiveModal');
}

// Close modal when clicking outside
window.onclick = function(event){
    if(event.target.classList.contains('modal')){
        event.target.style.display = 'none';
    }
}
</script>
</body>
</html>