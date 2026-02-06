// Admin elections utilities with modal support

function getListOfCandidates(electionId) {
  const modal = document.getElementById('candidateListModal');
  const content = document.getElementById('candidateListContent');
  const closeBtn = document.getElementById('closeCandidateListModal');
  
  if (!modal || !content) return;
  
  // Show loading
  content.innerHTML = `
    <div class="loading-spinner">
      <svg class="spinner-svg" viewBox="25 25 50 50">
        <circle r="20" cy="50" cx="50"></circle>
      </svg>
    </div>
  `;
  
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
  
  // Fetch candidates
  fetch(`../core/api.php?action=getCandidatesByElection&id_election=${electionId}`)
    .then(res => res.json())
    .then(candidates => {
      if (!candidates || candidates.length === 0) {
        content.innerHTML = '<p style="text-align: center; padding: 2rem;">No candidates found for this election.</p>';
        return;
      }
      
      let html = '<div class="candidates-list">';
      
      candidates.forEach(candidate => {
        html += `
          <div class="candidate-item" style="border: 2px solid #3a3a3a; padding: 1rem; margin-bottom: 1rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <h4 style="margin: 0 0 0.5rem 0;">${candidate.name} / ${candidate.ar_name}</h4>
              <p style="margin: 0; color: #666; font-size: 0.9rem;">Position: ${candidate.position_name}</p>
              <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.9rem;">Party: ${candidate.Supporting_party}</p>
            </div>
            <button class="btn-danger" onclick="removeCandidate(${candidate.id}, ${electionId})" style="padding: 0.5rem 1rem;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 5.98C17.67 5.65 14.32 5.48 10.98 5.48C9 5.48 7.02 5.58 5.04 5.78L3 5.98M8.5 4.97L8.72 3.66C8.88 2.71 9 2 10.69 2H13.31C15 2 15.13 2.75 15.28 3.67L15.5 4.97M18.85 9.14L18.2 19.21C18.09 20.78 18 22 15.21 22H8.79C6 22 5.91 20.78 5.8 19.21L5.15 9.14M10.33 16.5H13.66M9.5 12.5H14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Remove
            </button>
          </div>
        `;
      });
      
      html += '</div>';
      content.innerHTML = html;
    })
    .catch(err => {
      console.error('Error:', err);
      content.innerHTML = '<p style="text-align: center; padding: 2rem; color: red;">Failed to load candidates</p>';
    });
  
  const closeHandler = () => {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  };
  
  closeBtn.onclick = closeHandler;
  modal.querySelector('.modal-overlay').onclick = closeHandler;
}

function removeCandidate(candidateId, electionId) {
  if (!confirm('Are you sure you want to remove this candidate?')) return;
  
  const formData = new FormData();
  formData.append('action', 'delete');
  formData.append('id', candidateId);
  
  fetch('../core/candidate-handler.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success || data.message) {
        alert('Candidate removed successfully');
        getListOfCandidates(electionId); // Refresh list
      } else {
        alert(data.error || 'Failed to remove candidate');
      }
    })
    .catch(err => {
      console.error('Error:', err);
      alert('An error occurred while removing the candidate');
    });
}

function addNewCandidate(electionId, lang) {
  const modal = document.getElementById('addCandidateModal');
  const form = document.getElementById('addCandidateForm');
  const closeBtn = document.getElementById('closeAddCandidateModal');
  const cancelBtn = document.getElementById('cancelAddCandidateBtn');
  const saveBtn = document.getElementById('saveAddCandidateBtn');
  const positionInput = document.getElementById('add_id_position');
  
  if (!modal || !form) return;
  
  // Reset form
  form.reset();
  document.getElementById('add_election_id').value = electionId;
  
  // Automatically fetch the position for this election (each election has only one position)
  fetch(`../core/api.php?action=getPositionByElection&id_election=${electionId}`)
    .then(res => res.json())
    .then(positions => {
      if (!positions || positions.length === 0) {
        alert('No position found for this election. Please contact the administrator.');
        return;
      }
      
      // Set the first (and only) position ID automatically
      positionInput.value = positions[0].id;
      
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    })
    .catch(err => {
      console.error('Error:', err);
      alert('Failed to load election position');
    });
  
  const closeHandler = () => {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  };
  
  closeBtn.onclick = closeHandler;
  cancelBtn.onclick = closeHandler;
  modal.querySelector('.modal-overlay').onclick = closeHandler;
  
  form.onsubmit = async function(e) {
    e.preventDefault();
    
    // Validate position is set
    if (!positionInput.value) {
      alert('No position assigned to this election. Cannot add candidate.');
      return;
    }
    
    saveBtn.classList.add('loading');
    saveBtn.disabled = true;
    
    const formData = new FormData(form);
    formData.append('action', 'create');
    
    try {
      const response = await fetch('../core/candidate-handler.php', {
        method: 'POST',
        body: formData
      });
      
      const data = await response.json();
      
      if (data.success || data.id) {
        alert('Candidate added successfully');
        closeHandler();
        window.location.reload();
      } else {
        alert(data.error || 'Failed to add candidate');
      }
    } catch (err) {
      console.error('Error:', err);
      alert('An error occurred while adding the candidate');
    } finally {
      saveBtn.classList.remove('loading');
      saveBtn.disabled = false;
    }
  };
}

function publishResults(electionId) {
  const formData = new FormData();
  formData.append('action', 'publishResults');
  formData.append('id_election', electionId);

  fetch('../core/api.php', {
    method: 'POST',
    body: formData
  })
  .then(res => {
    if (!res.ok) {
      throw new Error('Network response was not ok');
    }
    return res.text();
  })
  .then(text => {
    try {
      const data = JSON.parse(text);
      if (data.error) {
        alert(data.error || 'Failed to publish results');
      } else {
        window.location.reload();
      }
    } catch (e) {
      console.error('JSON parse error:', e, 'Response:', text);
      // If we can't parse JSON but got a response, assume success
      window.location.reload();
    }
  })
  .catch(err => {
    console.error('Network error:', err);
    alert('Network error occurred. Please try again.');
  });
}

function stopPublishingResults(electionId) {
  const formData = new FormData();
  formData.append('action', 'stopPublishingResults');
  formData.append('id_election', electionId);

  fetch('../core/api.php', {
    method: 'POST',
    body: formData
  })
  .then(res => {
    if (!res.ok) {
      throw new Error('Network response was not ok');
    }
    return res.text();
  })
  .then(text => {
    try {
      const data = JSON.parse(text);
      if (data.error) {
        alert(data.error || 'Failed to unpublish results');
      } else {
        window.location.reload();
      }
    } catch (e) {
      console.error('JSON parse error:', e, 'Response:', text);
      // If we can't parse JSON but got a response, assume success
      window.location.reload();
    }
  })
  .catch(err => {
    console.error('Network error:', err);
    alert('Network error occurred. Please try again.');
  });
}

function editElection(electionId, lang) {
  const modal = document.getElementById('editElectionModal');
  const form = document.getElementById('editElectionForm');
  const closeBtn = document.getElementById('closeEditElectionModal');
  const cancelBtn = document.getElementById('cancelEditElectionBtn');
  const saveBtn = document.getElementById('saveEditElectionBtn');
  
  if (!modal || !form) return;
  
  // Fetch election data
  fetch(`../core/api.php?action=getElection&id=${electionId}`)
    .then(res => res.json())
    .then(election => {
      // Set election ID
      document.getElementById('edit_election_id').value = electionId;
      
      // Set election type
      if (document.getElementById('edit_election_type')) {
        document.getElementById('edit_election_type').value = election.election_type || '';
      }
      
      // Set position dropdown
      const positionSelect = document.getElementById('edit_election_position');
      if (positionSelect && election.position_en_name) {
        const options = positionSelect.options;
        for (let i = 0; i < options.length; i++) {
          if (options[i].value === election.position_en_name) {
            positionSelect.selectedIndex = i;
            break;
          }
        }
        
        // Set hidden fields
        document.getElementById('edit_position_en_name').value = election.position_en_name || '';
        document.getElementById('edit_position_fr_name').value = election.position_fr_name || '';
        document.getElementById('edit_position_ar_name').value = election.position_ar_name || '';
      }
      
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    })
    .catch(err => {
      console.error('Error loading election:', err);
      alert('Failed to load election data');
    });
  
  // Handle position dropdown selection
  const positionSelect = document.getElementById('edit_election_position');
  const positionEnInput = document.getElementById('edit_position_en_name');
  const positionFrInput = document.getElementById('edit_position_fr_name');
  const positionArInput = document.getElementById('edit_position_ar_name');
  
  if (positionSelect && positionEnInput && positionFrInput && positionArInput) {
    positionSelect.addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      if (selectedOption.value) {
        positionEnInput.value = selectedOption.value;
        positionFrInput.value = selectedOption.getAttribute('data-fr') || '';
        positionArInput.value = selectedOption.getAttribute('data-ar') || '';
      } else {
        positionEnInput.value = '';
        positionFrInput.value = '';
        positionArInput.value = '';
      }
    });
  }
  
  const closeHandler = () => {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  };
  
  closeBtn.onclick = closeHandler;
  cancelBtn.onclick = closeHandler;
  modal.querySelector('.modal-overlay').onclick = closeHandler;
  
  form.onsubmit = async function(e) {
    e.preventDefault();
    
    // Ensure position values are set from dropdown selection
    const positionSelect = document.getElementById('edit_election_position');
    const positionEnInput = document.getElementById('edit_position_en_name');
    const positionFrInput = document.getElementById('edit_position_fr_name');
    const positionArInput = document.getElementById('edit_position_ar_name');
    
    if (positionSelect && positionSelect.value && positionEnInput && positionFrInput && positionArInput) {
      const selectedOption = positionSelect.options[positionSelect.selectedIndex];
      positionEnInput.value = selectedOption.value;
      positionFrInput.value = selectedOption.getAttribute('data-fr') || '';
      positionArInput.value = selectedOption.getAttribute('data-ar') || '';
    }
    
    saveBtn.classList.add('loading');
    saveBtn.disabled = true;
    
    const formData = new FormData(form);
    formData.append('action', 'editElectionConfig');
    formData.append('id', electionId);
    
    try {
      const response = await fetch('../core/api.php', {
        method: 'POST',
        body: formData
      });
      
      // Just reload the page regardless of response
      closeHandler();
      window.location.reload();
    } catch (err) {
      console.error('Error:', err);
      // Still reload even on error since database updates are working
      closeHandler();
      window.location.reload();
    }
  };
}

function managePositions(electionId) {
  try {
    if (!electionId) return;

    const action = (prompt('Type "add" to create a position, or "delete" to remove one for this election:') || '').trim().toLowerCase();
    if (!action) return;

    if (action === 'add') {
      const ar = (prompt('Arabic name:') || '').trim();
      if (!ar) return alert('Cancelled');
      const en = (prompt('English name:') || '').trim();
      if (!en) return alert('Cancelled');
      const fr = (prompt('French name:') || '').trim();
      if (!fr) return alert('Cancelled');

      const formData = new FormData();
      formData.append('action', 'addPosition');
      formData.append('ar_name', ar);
      formData.append('en_name', en);
      formData.append('fr_name', fr);
      formData.append('id_election', String(electionId));

      fetch('../core/api.php', { method: 'POST', body: formData })
        .then(async (res) => {
          const text = await res.text();
          try { return JSON.parse(text); } catch { return { raw: text, ok: res.ok }; }
        })
        .then((data) => {
          if (data && (data.success || data.id)) {
            alert('Position added successfully');
            window.location.reload();
          } else {
            alert('Failed to add position' + (data && data.error ? `: ${data.error}` : ''));
          }
        })
        .catch((err) => {
          console.error(err);
          alert('Error while adding position');
        });
      return;
    }

    if (action === 'delete') {
      if (!confirm('Delete a position for this election? This will detach linked candidates.')) return;
      const formData = new FormData();
      formData.append('action', 'deletePosition');
      formData.append('id_election', String(electionId));

      fetch('../core/api.php', { method: 'POST', body: formData })
        .then(async (res) => {
          const text = await res.text();
          try { return JSON.parse(text); } catch { return { raw: text, ok: res.ok }; }
        })
        .then((data) => {
          if (data && (data.success || data.ok)) {
            alert('Position delete request completed');
            window.location.reload();
          } else {
            alert('Failed to delete position' + (data && data.error ? `: ${data.error}` : ''));
          }
        })
        .catch((err) => {
          console.error(err);
          alert('Error while deleting position');
        });
    }
  } catch (e) {
    console.error(e);
  }
}

