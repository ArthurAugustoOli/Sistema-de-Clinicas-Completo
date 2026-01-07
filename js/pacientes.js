// Inicialização de componentes
$(document).ready(() => {
  // Inicializar máscaras
  $("#cpf").mask("000.000.000-00")
  $("#telefone").mask("(00) 00000-0000")
  $("#whatsapp").mask("(00) 00000-0000")
  $("#telefone_emergencia").mask("(00) 00000-0000")
  $("#cep").mask("00000-000")

  // Inicializar datepickers
  flatpickr(".datepicker", {
    locale: "pt",
    dateFormat: "d/m/Y",
    allowInput: true,
  })

  flatpickr(".datetimepicker", {
    locale: "pt",
    dateFormat: "d/m/Y H:i",
    enableTime: true,
    time_24hr: true,
    allowInput: true,
  })

  // Alternar entre visualizações
  $(".view-toggle-btn").click(function () {
    $(".view-toggle-btn").removeClass("active")
    $(this).addClass("active")

    const view = $(this).data("view")
    if (view === "card") {
      $("#cardView").show()
      $("#listView").hide()
    } else {
      $("#cardView").hide()
      $("#listView").show()
    }
  })

  // Limpar pesquisa
  $("#clearSearch").click(() => {
    window.location.href = "?page=1&limit=" + $("#limitSelect").val()
  })

  // Pesquisar ao digitar
  $("#searchInput").on("keyup", function (e) {
    if (e.key === "Enter") {
      const searchTerm = $(this).val().trim()
      window.location.href = "?page=1&limit=" + $("#limitSelect").val() + "&search=" + encodeURIComponent(searchTerm)
    }
  })

  // Alterar itens por página
  $("#limitSelect").change(function () {
    const limit = $(this).val()
    const search = new URLSearchParams(window.location.search).get("search") || ""
    window.location.href = "?page=1&limit=" + limit + "&search=" + encodeURIComponent(search)
  })

  // Mesmo número de telefone para WhatsApp
  $("#mesmo_telefone").change(function () {
    if ($(this).is(":checked")) {
      $("#whatsapp").val($("#telefone").val())
      $("#whatsapp").prop("disabled", true)
    } else {
      $("#whatsapp").prop("disabled", false)
    }
  })

  // Buscar CEP
  $("#buscarCep").click(() => {
    const cep = $("#cep").val().replace(/\D/g, "")

    if (cep.length !== 8) {
      showToast("CEP inválido", "error")
      return
    }

    $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, (data) => {
      if (!data.erro) {
        $("#logradouro").val(data.logradouro)
        $("#bairro").val(data.bairro)
        $("#cidade").val(data.localidade)
        $("#estado").val(data.uf)
        $("#numero").focus()
      } else {
        showToast("CEP não encontrado", "error")
      }
    }).fail(() => {
      showToast("Erro ao buscar CEP", "error")
    })
  })

  // Mostrar/ocultar campos de convênio
  $("#tem_convenio").change(function () {
    if ($(this).is(":checked")) {
      $(".convenio-fields").show()
    } else {
      $(".convenio-fields").hide()
    }
  })

  // Prévia de documentos
  $("#documentos").change(function () {
    const files = this.files
    let previewHtml = ""

    // Limpar container de descrições
    $("#documentos-container").html("")

    for (let i = 0; i < files.length; i++) {
      const file = files[i]
      const fileName = file.name
      const fileExt = fileName.split(".").pop().toLowerCase()

      let icon = "bi-file-earmark"
      if (["pdf"].includes(fileExt)) {
        icon = "bi-file-earmark-pdf"
      } else if (["jpg", "jpeg", "png", "gif"].includes(fileExt)) {
        icon = "bi-file-earmark-image"
      } else if (["doc", "docx"].includes(fileExt)) {
        icon = "bi-file-earmark-word"
      }

      // Adicionar prévia
      previewHtml += `
            <div class="col-md-4 mb-2">
                <div class="card">
                    <div class="card-body p-2 text-center">
                        <i class="bi ${icon} fs-1"></i>
                        <p class="mb-0 text-truncate">${fileName}</p>
                    </div>
                </div>
            </div>`

      // Adicionar campo de descrição
      $("#documentos-container").append(`
            <div class="documento-item mb-3">
                <div class="row">
                    <div class="col-md-12">
                        <label class="form-label">Descrição do Documento: ${fileName}</label>
                        <input type="text" class="form-control documento-descricao" name="documento_descricao[]" placeholder="Ex: Exame de sangue, Raio-X, Receita médica...">
                    </div>
                </div>
            </div>
            `)
    }

    $("#documentos-preview").html(previewHtml)
  })

  // Submeter formulário de novo paciente
$("#addPacienteForm").off("submit").on("submit", function (e) {

  e.preventDefault()

  const formData = new FormData(this)

  if (!$("#foto_perfil").val()) {
    formData.append("usar_foto_padrao", "1")
  }

  $.ajax({
    url: "../api/pacientes/criar.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: (response) => {
      if (response.success) {
        showToast("Paciente cadastrado com sucesso!", "success")
        $("#addPacienteModal").modal("hide")
        setTimeout(() => {
          window.location.reload()
        }, 1000)
      } else {
        showToast(response.message || "Erro ao cadastrar paciente", "error")
      }
    },
    error: (xhr, status, error) => {
      console.error("Erro:", xhr.responseText)
      showToast("Erro ao cadastrar paciente: " + error, "error")
    },
  })
})

  // Garante que o submit não seja vinculado múltiplas vezes
$(document).ready(function () {
  $("#addPacienteForm").off("submit").on("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    // Remove qualquer alerta anterior
    $(".toast").remove();

    $.ajax({
      url: "../api/pacientes/criar.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: (response) => {
        // Remove toasts existentes antes de mostrar outro
        $(".toast").remove();

        if (response.success) {
          showToast("Paciente cadastrado com sucesso!", "success");
          $("#addPacienteModal").modal("hide");
          this.reset();
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          showToast(response.message || "Erro ao cadastrar paciente", "error");
        }
      },
      error: () => {
        $(".toast").remove(); // remove duplicados
        showToast("Erro ao cadastrar paciente", "error");
      },
    });
  });
});



  // Add this to your $(document).ready() function
  $("#editPacienteForm").on("submit", function (e) {
    e.preventDefault()

    const formData = new FormData(this)

    $.ajax({
      url: "../api/pacientes/atualizar.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: (response) => {
        if (response.success) {
          showToast("Paciente atualizado com sucesso!", "success")
          $("#editPacienteModal").modal("hide")
          setTimeout(() => {
            window.location.reload()
          }, 1000)
        } else {
          showToast(response.message || "Erro ao atualizar paciente", "error")
        }
      },
      error: () => {
        showToast("Erro ao atualizar paciente", "error")
      },
    })
  })
})

// Funções para manipulação de pacientes


// In the editPaciente function, modify the form submission part:

function editPaciente(cpf) {
  // Fechar modal de visualização se estiver aberto
  $("#viewPacienteModal").modal("hide")

  $.ajax({
    url: "../api/pacientes/detalhes.php",
    type: "GET",
    data: { cpf: cpf },
    dataType: "json",
    success: (response) => {
      if (response.success) {
        const paciente = response.data.paciente
        const documentos = response.data.documentos || []

        // Preencher o formulário de edição
        $("#edit_cpf").val(paciente.cpf)

        // Criar o conteúdo do formulário de edição
        let formHtml = `
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="edit_nome" class="form-label">Nome Completo*</label>
                            <input type="text" class="form-control" id="edit_nome" name="nome" value="${paciente.nome || ""}" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_cpf" class="form-label">CPF*</label>
                                    <input type="text" class="form-control" id="edit_cpf" name="cpf" value="${paciente.cpf || ""}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_data_nasc" class="form-label">Data de Nascimento*</label>
                                    <input type="text" class="form-control datepicker" id="edit_data_nasc" name="data_nasc" value="${formatDate(paciente.data_nasc) || ""}" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="mb-3">
                            <label for="edit_foto_perfil" class="form-label">Foto de Perfil</label>
                            <div class="d-flex flex-column align-items-center">
                                <div class="position-relative mb-2">
                                    <img id="edit_preview_foto" src="${paciente.foto_perfil ? "../" + paciente.foto_perfil : "../assets/img/profile-placeholder.png"}" class="img-thumbnail rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                                    <button type="button" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle" style="width: 30px; height: 30px; padding: 0;" onclick="document.getElementById('edit_foto_perfil').click()">
                                        <i class="bi bi-camera"></i>
                                    </button>
                                </div>
                                <input type="file" class="form-control d-none" id="edit_foto_perfil" name="foto_perfil" accept="image/*" onchange="previewImage(this, 'edit_preview_foto')">
                                <small class="form-text text-muted">Clique para alterar a foto</small>
                            </div>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-3" id="editPacienteTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="edit-contato-tab" data-bs-toggle="tab" data-bs-target="#edit-contato" type="button" role="tab" aria-controls="edit-contato" aria-selected="true">Contato</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="edit-endereco-tab" data-bs-toggle="tab" data-bs-target="#edit-endereco" type="button" role="tab" aria-controls="edit-endereco" aria-selected="false">Endereço</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="edit-saude-tab" data-bs-toggle="tab" data-bs-target="#edit-saude" type="button" role="tab" aria-controls="edit-saude" aria-selected="false">Saúde</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="edit-observacoes-tab" data-bs-toggle="tab" data-bs-target="#edit-observacoes" type="button" role="tab" aria-controls="edit-observacoes" aria-selected="false">Observações</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="edit-documentos-tab" data-bs-toggle="tab" data-bs-target="#edit-documentos" type="button" role="tab" aria-controls="edit-documentos" aria-selected="false">Documentos</button>
                    </li>
                </ul>

                <div class="tab-content" id="editPacienteTabContent">
                    <!-- Aba de Contato -->
                    <div class="tab-pane fade show active" id="edit-contato" role="tabpanel" aria-labelledby="edit-contato-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_telefone" class="form-label">Telefone*</label>
                                    <input type="text" class="form-control" id="edit_telefone" name="telefone" value="${paciente.telefone || ""}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit_email" name="email" value="${paciente.email || ""}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_nome_contato_emergencia" class="form-label">Contato de Emergência</label>
                                    <input type="text" class="form-control" id="edit_nome_contato_emergencia" name="nome_contato_emergencia" value="${paciente.nome_contato_emergencia || ""}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_numero_contato_emergencia" class="form-label">Telefone de Emergência</label>
                                    <input type="text" class="form-control" id="edit_numero_contato_emergencia" name="numero_contato_emergencia" value="${paciente.numero_contato_emergencia || ""}">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_filiacao_contato_emergencia" class="form-label">Filiação do Contato de Emergência</label>
                            <input type="text" class="form-control" id="edit_filiacao_contato_emergencia" name="filiacao_contato_emergencia" value="${paciente.filiacao_contato_emergencia || ""}">
                        </div>
                    </div>

                    <!-- Aba de Endereço -->
                    <div class="tab-pane fade" id="edit-endereco" role="tabpanel" aria-labelledby="edit-endereco-tab">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_cep" class="form-label">CEP</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="edit_cep" name="cep" value="${paciente.cep || ""}">
                                        <button class="btn btn-outline-secondary" type="button" id="edit_buscarCep">Buscar</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="edit_logradouro" class="form-label">Logradouro</label>
                                    <input type="text" class="form-control" id="edit_logradouro" name="logradouro" value="${paciente.logradouro || ""}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_numero" class="form-label">Número</label>
                                    <input type="text" class="form-control" id="edit_numero" name="numero" value="${paciente.numero || ""}">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="edit_complemento" class="form-label">Complemento</label>
                                    <input type="text" class="form-control" id="edit_complemento" name="complemento" value="${paciente.complemento || ""}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_bairro" class="form-label">Bairro</label>
                                    <input type="text" class="form-control" id="edit_bairro" name="bairro" value="${paciente.bairro || ""}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_cidade" class="form-label">Cidade</label>
                                    <input type="text" class="form-control" id="edit_cidade" name="cidade" value="${paciente.cidade || ""}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="edit_estado" class="form-label">UF</label>
                                    <input type="text" class="form-control" id="edit_estado" name="estado" maxlength="2" value="${paciente.estado || ""}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aba de Saúde -->
                    <div class="tab-pane fade" id="edit-saude" role="tabpanel" aria-labelledby="edit-saude-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_tipo_sanguineo" class="form-label">Tipo Sanguíneo</label>
                                    <select class="form-select" id="edit_tipo_sanguineo" name="tipo_sanguineo">
                                        <option value="">Selecione</option>
                                        <option value="A+" ${paciente.tipo_sanguineo === "A+" ? "selected" : ""}>A+</option>
                                        <option value="A-" ${paciente.tipo_sanguineo === "A-" ? "selected" : ""}>A-</option>
                                        <option value="B+" ${paciente.tipo_sanguineo === "B+" ? "selected" : ""}>B+</option>
                                        <option value="B-" ${paciente.tipo_sanguineo === "B-" ? "selected" : ""}>B-</option>
                                        <option value="AB+" ${paciente.tipo_sanguineo === "AB+" ? "selected" : ""}>AB+</option>
                                        <option value="AB-" ${paciente.tipo_sanguineo === "AB-" ? "selected" : ""}>AB-</option>
                                        <option value="O+" ${paciente.tipo_sanguineo === "O+" ? "selected" : ""}>O+</option>
                                        <option value="O-" ${paciente.tipo_sanguineo === "O-" ? "selected" : ""}>O-</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_tem_convenio" class="form-label">Possui Convênio?</label>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="edit_tem_convenio" name="tem_convenio" value="1" ${paciente.tem_convenio == 1 ? "checked" : ""}>
                                        <label class="form-check-label" for="edit_tem_convenio">
                                            Sim, possui convênio
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row edit-convenio-fields" style="${paciente.tem_convenio == 1 ? "" : "display: none;"}">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_convenio" class="form-label">Convênio</label>
                                    <input type="text" class="form-control" id="edit_convenio" name="convenio" value="${paciente.convenio || ""}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_numero_convenio" class="form-label">Número da Carteirinha</label>
                                    <input type="text" class="form-control" id="edit_numero_convenio" name="numero_convenio" value="${paciente.numero_convenio || ""}">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_alergias" class="form-label">Alergias</label>
                            <textarea class="form-control" id="edit_alergias" name="alergias" rows="2">${paciente.alergias || ""}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_doencas" class="form-label">Doenças Crônicas</label>
                            <textarea class="form-control" id="edit_doencas" name="doencas" rows="2">${paciente.doencas || ""}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_condicoes_medicas" class="form-label">Condições Médicas</label>
                            <textarea class="form-control" id="edit_condicoes_medicas" name="condicoes_medicas" rows="2">${paciente.condicoes_medicas || ""}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_remedios_em_uso" class="form-label">Medicamentos em uso</label>
                            <textarea class="form-control" id="edit_remedios_em_uso" name="remedios_em_uso" rows="2">${paciente.remedios_em_uso || ""}</textarea>
                        </div>
                    </div>

                    <!-- Aba de Observações -->
                    <div class="tab-pane fade" id="edit-observacoes" role="tabpanel" aria-labelledby="edit-observacoes-tab">
                        <div class="mb-3">
                            <label for="edit_observacoes" class="form-label">Observações Gerais</label>
                            <textarea class="form-control" id="edit_observacoes" name="observacoes" rows="5">${paciente.observacoes || ""}</textarea>
                        </div>
                    </div>

                    <!-- Aba de Documentos -->
                    <div class="tab-pane fade" id="edit-documentos" role="tabpanel" aria-labelledby="edit-documentos-tab">
                        <div class="mb-3">
                            <label for="edit_documentos" class="form-label">Adicionar Novos Documentos</label>
                            <input type="file" class="form-control" id="edit_documentos" name="documentos[]" multiple>
                            <small class="form-text text-muted">Você pode selecionar múltiplos arquivos (PDF, imagens, etc.)</small>
                        </div>
                        <div id="edit-documentos-container">
                            <!-- Campos de descrição para novos documentos serão adicionados aqui via JavaScript -->
                        </div>
                        
                        <h6 class="mt-4 mb-3">Documentos Existentes</h6>
                        <div id="documentos-existentes" class="row">`

        // Adicionar documentos existentes
        if (documentos.length > 0) {
          documentos.forEach((doc) => {
            const fileExt = doc.nome_documento.split(".").pop().toLowerCase()
            let icon = "bi-file-earmark"
            if (["pdf"].includes(fileExt)) {
              icon = "bi-file-earmark-pdf"
            } else if (["jpg", "jpeg", "png", "gif"].includes(fileExt)) {
              icon = "bi-file-earmark-image"
            }

            formHtml += `
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi ${icon} fs-3 me-2"></i>
                                        <div>
                                            <p class="mb-0 text-truncate">${doc.nome_documento}</p>
                                            <small class="text-muted">${formatDate(doc.data_upload)}</small>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Descrição do Documento</label>
                                        <input type="text" class="form-control" name="documento_descricao_existente[${doc.id}]" value="${doc.descricao || ""}" placeholder="Descreva este documento">
                                        <input type="hidden" name="documento_id_existente[]" value="${doc.id}">
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <a href="../${doc.caminho_arquivo}" target="_blank" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> Visualizar
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removerDocumento(${doc.id})">
                                            <i class="bi bi-trash"></i> Remover
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>`
          })
        } else {
          formHtml += '<div class="alert alert-info">Nenhum documento cadastrado.</div>'
        }

        formHtml += `
                        </div>
                    </div>
                </div>`

        // Preencher o formulário
        $("#editPacienteForm").html(formHtml)

        // Inicializar máscaras
        $("#edit_telefone").mask("(00) 00000-0000")
        $("#edit_numero_contato_emergencia").mask("(00) 00000-0000")
        $("#edit_cep").mask("00000-000")

        // Inicializar datepicker
        flatpickr("#edit_data_nasc", {
          locale: "pt",
          dateFormat: "d/m/Y",
          allowInput: true,
        })

        // Mostrar/ocultar campos de convênio
        $("#edit_tem_convenio").change(function () {
          if ($(this).is(":checked")) {
            $(".edit-convenio-fields").show()
          } else {
            $(".edit-convenio-fields").hide()
          }
        })

        // Buscar CEP
        $("#edit_buscarCep").click(() => {
          const cep = $("#edit_cep").val().replace(/\D/g, "")

          if (cep.length !== 8) {
            showToast("CEP inválido", "error")
            return
          }

          $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, (data) => {
            if (!data.erro) {
              $("#edit_logradouro").val(data.logradouro)
              $("#edit_bairro").val(data.bairro)
              $("#edit_cidade").val(data.localidade)
              $("#edit_estado").val(data.uf)
              $("#edit_numero").focus()
            } else {
              showToast("CEP não encontrado", "error")
            }
          }).fail(() => {
            showToast("Erro ao buscar CEP", "error")
          })
        })

        // Gerenciar novos documentos
        $("#edit_documentos").change(function () {
          const files = this.files

          // Limpar container de descrições
          $("#edit-documentos-container").html("")

          for (let i = 0; i < files.length; i++) {
            const file = files[i]
            const fileName = file.name

            // Adicionar campo de descrição para cada documento
            $("#edit-documentos-container").append(`
                        <div class="mb-3">
                            <label class="form-label">Descrição: ${fileName}</label>
                            <input type="text" class="form-control" name="documento_descricao_novo[]" placeholder="Ex: Exame de sangue, Raio-X, Receita médica...">
                        </div>
                        `)
          }
        })

        // IMPORTANTE: Configurar o envio do formulário AQUI, dentro da função editPaciente
        $("#editPacienteForm").off("submit").on("submit", function(e) {
          e.preventDefault()
          
          // Mostrar indicador de carregamento
          const submitBtn = $(this).find('button[type="submit"]');
          const originalText = submitBtn.html();
          submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span> Salvando...').prop('disabled', true);
          
          const formData = new FormData(this)
          
          // Verificar se o CPF está sendo enviado
          if (!formData.get('cpf')) {
            formData.append('cpf', paciente.cpf);
          }
          
          $.ajax({
            url: "../api/pacientes/atualizar.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
              if (response.success) {
                showToast("Paciente atualizado com sucesso!", "success")
                $("#editPacienteModal").modal("hide")
                setTimeout(() => {
                  window.location.reload()
                }, 1000)
              } else {
                showToast(response.message || "Erro ao atualizar paciente", "error")
                submitBtn.html(originalText).prop('disabled', false);
                console.error("Erro na resposta:", response);
              }
            },
            error: (xhr, status, error) => {
              showToast("Erro ao atualizar paciente", "error")
              submitBtn.html(originalText).prop('disabled', false);
              console.error("Erro na requisição:", xhr.responseText);
            },
          })
        })

        // Exibir modal
        $("#editPacienteModal").modal("show")
      } else {
        showToast(response.message || "Erro ao carregar dados do paciente", "error")
      }
    },
    error: (xhr) => {
      showToast("Erro ao carregar dados do paciente", "error")
      console.error("Erro na requisição:", xhr.responseText);
    },
  })
}

function confirmDelete(cpf, nome) {
  $("#pacienteNomeDelete").text(nome)
  $("#confirmDeleteBtn").attr("onclick", `deletePaciente('${cpf}')`)
  $("#deleteConfirmModal").modal("show")
}

function deletePaciente(cpf) {
  $.ajax({
    url: "../api/pacientes/excluir.php",
    type: "POST",
    data: { cpf: cpf },
    dataType: "json",
    success: (response) => {
      if (response.success) {
        showToast("Paciente excluído com sucesso!", "success")
        $("#deleteConfirmModal").modal("hide")
        setTimeout(() => {
          window.location.reload()
        }, 1000)
      } else {
        showToast(response.message || "Erro ao excluir paciente", "error")
      }
    },
    error: () => {
      showToast("Erro ao excluir paciente", "error")
    },
  })
}

function agendarConsulta(cpf) {
  // Fechar outros modais se estiverem abertos
  $("#viewPacienteModal").modal("hide")

  // Carregar profissionais e procedimentos
  $.ajax({
    url: "../api/consultas/opcoes.php",
    type: "GET",
    dataType: "json",
    success: (response) => {
      if (response.success) {
        // Preencher select de profissionais
        let profissionaisHtml = '<option value="">Selecione um profissional</option>'
        response.data.profissionais.forEach((profissional) => {
          profissionaisHtml += `<option value="${profissional.id}">${profissional.nome}</option>`
        })
        $("#profissional_id").html(profissionaisHtml)

        // Preencher select de procedimentos
        let procedimentosHtml = '<option value="">Selecione um procedimento</option>'
        response.data.procedimentos.forEach((procedimento) => {
          procedimentosHtml += `<option value="${procedimento.nome}">${procedimento.nome}</option>`
        })
        $("#procedimento").html(procedimentosHtml)

        // Definir CPF do paciente
        $("#paciente_cpf").val(cpf)

        // Inicializar datepicker
        flatpickr("#data_consulta", {
          locale: "pt",
          dateFormat: "d/m/Y H:i",
          enableTime: true,
          time_24hr: true,
          allowInput: true,
          minDate: "today",
        })

        // Configurar envio do formulário
        $("#salvarConsultaBtn")
          .off("click")
          .on("click", () => {
            const formData = new FormData(document.getElementById("agendarConsultaForm"))

            $.ajax({
              url: "../api/consultas/agendar.php",
              type: "POST",
              data: formData,
              processData: false,
              contentType: false,
              success: (response) => {
                if (response.success) {
                  showToast("Consulta agendada com sucesso!", "success")
                  $("#agendarConsultaModal").modal("hide")
                  setTimeout(() => {
                    window.location.reload()
                  }, 1000)
                } else {
                  showToast(response.message || "Erro ao agendar consulta", "error")
                }
              },
              error: () => {
                showToast("Erro ao agendar consulta", "error")
              },
            })
          })

        // Exibir modal
        $("#agendarConsultaModal").modal("show")
      } else {
        showToast(response.message || "Erro ao carregar opções", "error")
      }
    },
    error: () => {
      showToast("Erro ao carregar opções", "error")
    },
  })
}

// Função para remover documento
function removerDocumento(documentoId) {
  if (confirm("Tem certeza que deseja remover este documento?")) {
    $.ajax({
      url: "../api/pacientes/remover_documentos.php",
      type: "POST",
      data: { documento_id: documentoId },
      dataType: "json",
      success: (response) => {
        if (response.success) {
          showToast("Documento removido com sucesso!", "success")
          // Remover o elemento do DOM
          $(`input[name="documento_id_existente[]"][value="${documentoId}"]`).closest(".col-md-4").remove()
        } else {
          showToast(response.message || "Erro ao remover documento", "error")
        }
      },
      error: () => {
        showToast("Erro ao remover documento", "error")
      },
    })
  }
}

// Funções utilitárias
function previewImage(input, previewId) {
  if (input.files && input.files[0]) {
    const reader = new FileReader()

    reader.onload = (e) => {
      document.getElementById(previewId).src = e.target.result
    }

    reader.readAsDataURL(input.files[0])
  }
}

function createInfoItem(label, value) {
  return `
    <div class="patient-info-item">
        <div class="label">${label}</div>
        <div class="value">${value}</div>
    </div>`
}

function formatCPF(cpf) {
  if (!cpf) return "Não informado"

  cpf = cpf.replace(/\D/g, "")

  if (cpf.length !== 11) return cpf

  return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4")
}

function formatPhone(phone) {
  if (!phone) return "Não informado"

  phone = phone.replace(/\D/g, "")

  if (phone.length === 11) {
    return phone.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3")
  } else if (phone.length === 10) {
    return phone.replace(/(\d{2})(\d{4})(\d{4})/, "($1) $2-$3")
  }

  return phone
}

function formatDate(dateStr) {
  if (!dateStr) return "Não informada"

  const date = new Date(dateStr)
  return date.toLocaleDateString("pt-BR")
}

function formatDateTime(dateTimeStr) {
  if (!dateTimeStr) return "Não informada"

  const date = new Date(dateTimeStr)
  return (
    date.toLocaleDateString("pt-BR") +
    " " +
    date.toLocaleTimeString("pt-BR", {
      hour: "2-digit",
      minute: "2-digit",
    })
  )
}

function calcularIdade(dateStr) {
  if (!dateStr) return 0

  const birthDate = new Date(dateStr)
  const today = new Date()

  let age = today.getFullYear() - birthDate.getFullYear()
  const monthDiff = today.getMonth() - birthDate.getMonth()

  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age--
  }

  return age
}

function showToast(message, type = "success") {
  Toastify({
    text: message,
    duration: 3000,
    close: true,
    gravity: "top",
    position: "right",
    backgroundColor: type === "success" ? "#4e73df" : "#e74a3b",
    stopOnFocus: true,
  }).showToast()
}
