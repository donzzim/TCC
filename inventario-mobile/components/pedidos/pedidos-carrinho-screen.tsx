import MaterialIcons from '@expo/vector-icons/MaterialIcons';
import { router, type Href } from 'expo-router';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { AppMenuButton } from '@/components/app-menu-button';
import { ApiError, NetworkError } from '@/src/api/errors';
import {
  pedidosApi,
  type ComplementoSetorPedido,
  type MaterialPedido,
  type PedidoTipo,
  type TipoAtendimentoPermanente,
} from '@/src/api/pedidos';
import { useThemeStyles } from '@/src/theme/useThemeStyles';

type PedidoRoute = '/pedidos/consumo' | '/pedidos/permanentes';
type NotificationTone = 'success' | 'error' | 'info';

interface CartState {
  quantity: number;
  tipoAtendimento: TipoAtendimentoPermanente;
  justificativa: string;
  patrimonioSubstituido: string;
}

interface NotificationState {
  tone: NotificationTone;
  message: string;
}

interface PedidosCarrinhoScreenProps {
  tipo: PedidoTipo;
  title: string;
  subtitle: string;
  icon: keyof typeof MaterialIcons.glyphMap;
  accentColor: string;
  currentRoute: PedidoRoute;
  summaryTitle: string;
  helperText: string;
}

const PER_PAGE = 25;

const TABS: { href: PedidoRoute; label: string; icon: keyof typeof MaterialIcons.glyphMap }[] = [
  {
    href: '/pedidos/consumo',
    label: 'Consumo',
    icon: 'shopping-basket',
  },
  {
    href: '/pedidos/permanentes',
    label: 'Permanentes',
    icon: 'inventory-2',
  },
];

function formatMoney(value: number | null | undefined): string {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(value ?? 0);
}

function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError || error instanceof NetworkError) {
    return error.message;
  }

  return 'Nao foi possivel processar o pedido.';
}

function emptyCartState(): CartState {
  return {
    quantity: 0,
    tipoAtendimento: 'adicao',
    justificativa: '',
    patrimonioSubstituido: '',
  };
}

export function PedidosCarrinhoScreen({
  tipo,
  title,
  subtitle,
  icon,
  accentColor,
  currentRoute,
  summaryTitle,
  helperText,
}: PedidosCarrinhoScreenProps) {
  const themed = useThemeStyles();
  const accent = accentColor === '#2F855A' ? themed.colors.success : themed.colors.primary;
  const [materials, setMaterials] = useState<MaterialPedido[]>([]);
  const [complementos, setComplementos] = useState<ComplementoSetorPedido[]>([]);
  const [selectedComplementoId, setSelectedComplementoId] = useState<number | null>(null);
  const [cart, setCart] = useState<Record<number, CartState>>({});
  const [cartMaterials, setCartMaterials] = useState<Record<number, MaterialPedido>>({});
  const [search, setSearch] = useState('');
  const [submittedSearch, setSubmittedSearch] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalMaterials, setTotalMaterials] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isComplementoModalVisible, setIsComplementoModalVisible] = useState(false);
  const [justificativaGeral, setJustificativaGeral] = useState('');
  const [notification, setNotification] = useState<NotificationState | null>(null);
  const scrollRef = useRef<ScrollView>(null);
  const materialsPanelYRef = useRef(0);

  const selectedItems = useMemo(() => {
    return Object.values(cartMaterials)
      .map((material) => ({
        material,
        state: cart[material.id] ?? emptyCartState(),
      }))
      .filter(({ state }) => state.quantity > 0);
  }, [cart, cartMaterials]);

  const selectedQuantity = selectedItems.reduce((total, item) => total + item.state.quantity, 0);
  const selectedComplemento = complementos.find((complemento) => complemento.id === selectedComplementoId) ?? null;
  const materialsCounterText = isLoading
    ? 'Carregando catálogo'
    : `${materials.length} de ${totalMaterials} material(is) nesta página`;
  const hasPreviousPage = currentPage > 1;
  const hasNextPage = hasMore || currentPage < lastPage;

  const loadContext = useCallback(async () => {
    const data = await pedidosApi.contexto();

    setComplementos(data.complementos);
    setSelectedComplementoId((current) => current ?? data.complementos[0]?.id ?? null);
  }, []);

  const loadMaterials = useCallback(async ({
    pageToLoad = 1,
    searchTerm = '',
  }: {
    pageToLoad?: number;
    searchTerm?: string;
  } = {}) => {
    setIsLoading(true);

    try {
      const data = await pedidosApi.materiais({
        tipo,
        page: pageToLoad,
        perPage: PER_PAGE,
        search: searchTerm,
      });

      setMaterials(data.materiais);
      setCurrentPage(data.meta.current_page);
      setTotalMaterials(data.meta.total);
      setLastPage(Math.max(data.meta.last_page, 1));
      setHasMore(data.meta.has_more);
    } catch (error) {
      setNotification({
        tone: 'error',
        message: getErrorMessage(error),
      });
      setHasMore(false);
    } finally {
      setIsLoading(false);
    }
  }, [tipo]);

  useEffect(() => {
    let isMounted = true;

    async function loadInitialData() {
      setIsLoading(true);

      try {
        await loadContext();

        if (isMounted) {
          await loadMaterials({ pageToLoad: 1, searchTerm: '' });
        }
      } catch (error) {
        if (isMounted) {
          setNotification({
            tone: 'error',
            message: getErrorMessage(error),
          });
          setIsLoading(false);
        }
      }
    }

    loadInitialData();

    return () => {
      isMounted = false;
    };
  }, [loadContext, loadMaterials]);

  function updateCartItem(materialId: number, updater: (state: CartState) => CartState) {
    setCart((currentCart) => {
      const nextState = updater(currentCart[materialId] ?? emptyCartState());
      const nextCart = { ...currentCart };

      if (nextState.quantity <= 0) {
        delete nextCart[materialId];
      } else {
        nextCart[materialId] = nextState;
      }

      return nextCart;
    });
  }

  function incrementMaterial(material: MaterialPedido) {
    setCartMaterials((currentMaterials) => ({
      ...currentMaterials,
      [material.id]: material,
    }));

    updateCartItem(material.id, (state) => ({
      ...state,
      quantity: state.quantity + 1,
    }));
  }

  function decrementMaterial(material: MaterialPedido) {
    updateCartItem(material.id, (state) => ({
      ...state,
      quantity: Math.max(state.quantity - 1, 0),
    }));

    const currentQuantity = cart[material.id]?.quantity ?? 0;

    if (currentQuantity <= 1) {
      setCartMaterials((currentMaterials) => {
        const nextMaterials = { ...currentMaterials };
        delete nextMaterials[material.id];

        return nextMaterials;
      });
    }
  }

  function setTipoAtendimento(materialId: number, tipoAtendimento: TipoAtendimentoPermanente) {
    updateCartItem(materialId, (state) => ({
      ...state,
      quantity: state.quantity || 1,
      tipoAtendimento,
    }));
  }

  function setItemText(materialId: number, key: 'justificativa' | 'patrimonioSubstituido', value: string) {
    updateCartItem(materialId, (state) => ({
      ...state,
      quantity: state.quantity || 1,
      [key]: value,
    }));
  }

  function handleSearch() {
    const nextSearch = search.trim();

    setSubmittedSearch(nextSearch);
    loadMaterials({
      pageToLoad: 1,
      searchTerm: nextSearch,
    });
  }

  async function handlePageChange(nextPage: number) {
    if (isLoading || nextPage < 1 || nextPage > lastPage || nextPage === currentPage) {
      return;
    }

    await loadMaterials({
      pageToLoad: nextPage,
      searchTerm: submittedSearch,
    });

    scrollRef.current?.scrollTo({
      y: Math.max(materialsPanelYRef.current - 12, 0),
      animated: true,
    });
  }

  function clearCart() {
    setCart({});
    setCartMaterials({});
    setJustificativaGeral('');
    setNotification({
      tone: 'info',
      message: 'Carrinho limpo.',
    });
  }

  function validateBeforeSubmit(): boolean {
    if (!selectedComplementoId) {
      setNotification({
        tone: 'error',
        message: 'Selecione o complemento do setor.',
      });
      return false;
    }

    if (selectedItems.length === 0) {
      setNotification({
        tone: 'error',
        message: 'Adicione ao menos um material ao carrinho.',
      });
      return false;
    }

    if (tipo === 'consumo' && justificativaGeral.trim() === '') {
      setNotification({
        tone: 'error',
        message: 'Informe a justificativa do pedido.',
      });
      return false;
    }

    if (tipo === 'permanente') {
      const incomplete = selectedItems.find(({ state }) => {
        return state.justificativa.trim() === ''
          || (state.tipoAtendimento === 'substituicao' && state.patrimonioSubstituido.trim() === '');
      });

      if (incomplete) {
        setNotification({
          tone: 'error',
          message: 'Preencha justificativa e patrimonio quando houver substituicao.',
        });
        return false;
      }
    }

    return true;
  }

  async function submitPedido() {
    if (!validateBeforeSubmit() || !selectedComplementoId) {
      return;
    }

    setIsSubmitting(true);

    try {
      const response = await pedidosApi.criar({
        tipo,
        complemento_setor_id: selectedComplementoId,
        justificativa: justificativaGeral.trim(),
        itens: selectedItems.map(({ material, state }) => ({
          material_id: material.id,
          quantidade: state.quantity,
          tipo_atendimento: tipo === 'permanente' ? state.tipoAtendimento : undefined,
          justificativa: tipo === 'permanente' ? state.justificativa.trim() : undefined,
          patrimonio_substituido: tipo === 'permanente'
            ? state.patrimonioSubstituido.trim()
            : undefined,
        })),
      });

      setCart({});
      setCartMaterials({});
      setJustificativaGeral('');
      setNotification({
        tone: 'success',
        message: `Pedido #${response.pedido.id} cadastrado com sucesso.`,
      });
    } catch (error) {
      setNotification({
        tone: 'error',
        message: getErrorMessage(error),
      });
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <SafeAreaView style={[styles.safeArea, themed.screen]}>
      <Modal
        animationType="fade"
        transparent
        visible={isComplementoModalVisible}
        onRequestClose={() => setIsComplementoModalVisible(false)}>
        <View style={[styles.modalOverlay, themed.overlay]}>
          <View style={[styles.modalPanel, { backgroundColor: themed.colors.surface }]}>
            <View style={[styles.modalHeader, { borderBottomColor: themed.colors.border }]}>
              <Text style={[styles.modalTitle, themed.text]}>Complemento do setor</Text>
              <Pressable
                onPress={() => setIsComplementoModalVisible(false)}
                style={[styles.modalCloseButton, themed.primarySurface]}>
                <MaterialIcons name="close" size={21} color={themed.colors.primary} />
              </Pressable>
            </View>
            <ScrollView contentContainerStyle={styles.modalContent}>
              {complementos.map((complemento) => {
                const isActive = complemento.id === selectedComplementoId;

                return (
                  <Pressable
                    key={complemento.id}
                    onPress={() => {
                      setSelectedComplementoId(complemento.id);
                      setIsComplementoModalVisible(false);
                    }}
                    style={({ pressed }) => [
                      styles.complementoOption,
                      themed.mutedSurface,
                      isActive && { backgroundColor: themed.colors.primary },
                      pressed && styles.pressed,
                    ]}>
                    <Text
                      style={[
                        styles.complementoOptionText,
                        themed.text,
                        isActive && themed.onPrimaryText,
                      ]}>
                      {complemento.descricao}
                    </Text>
                    {isActive ? <MaterialIcons name="check" size={20} color={themed.colors.primaryText} /> : null}
                  </Pressable>
                );
              })}
            </ScrollView>
          </View>
        </View>
      </Modal>

      <ScrollView
        ref={scrollRef}
        style={styles.scroll}
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}>
        {notification ? (
          <View style={[
            styles.notification,
            {
              backgroundColor: notification.tone === 'success'
                ? themed.colors.success
                : notification.tone === 'error'
                  ? themed.colors.danger
                  : themed.colors.info,
            },
          ]}>
            <MaterialIcons
              name={notification.tone === 'success' ? 'check-circle' : notification.tone === 'error' ? 'error-outline' : 'info-outline'}
              size={20}
              color={themed.colors.primaryText}
            />
            <Text style={[styles.notificationText, themed.onPrimaryText]}>{notification.message}</Text>
            <Pressable onPress={() => setNotification(null)}>
              <MaterialIcons name="close" size={18} color={themed.colors.primaryText} />
            </Pressable>
          </View>
        ) : null}

        <View style={styles.header}>
          <AppMenuButton />
          <View style={styles.headerTextGroup}>
            <Text style={[styles.eyebrow, themed.mutedText]}>Pedidos</Text>
            <Text style={[styles.title, themed.text]}>{title}</Text>
            <Text style={[styles.subtitle, themed.mutedText]}>{subtitle}</Text>
          </View>
          <View style={[styles.headerIcon, { backgroundColor: `${accent}22` }]}>
            <MaterialIcons name={icon} size={24} color={accent} />
          </View>
        </View>

        <View style={[styles.tabs, themed.surface]}>
          {TABS.map((tab) => {
            const isActive = tab.href === currentRoute;

            return (
              <Pressable
                key={tab.href}
                onPress={() => router.replace(tab.href as Href)}
                style={({ pressed }) => [
                  styles.tab,
                  isActive && { backgroundColor: accent },
                  pressed && !isActive && styles.pressed,
                ]}>
                <MaterialIcons
                  name={tab.icon}
                  size={18}
                  color={isActive ? themed.colors.primaryText : themed.colors.primary}
                />
                <Text style={[styles.tabText, themed.primaryText, isActive && themed.onPrimaryText]}>
                  {tab.label}
                </Text>
              </Pressable>
            );
          })}
        </View>

        <View style={[styles.contextPanel, themed.surface]}>
          <View style={[styles.contextIcon, themed.primarySurface]}>
            <MaterialIcons name="storefront" size={22} color={themed.colors.primary} />
          </View>
          <View style={styles.contextText}>
            <Text style={[styles.contextTitle, themed.text]}>Pedido em rascunho</Text>
            <Text style={[styles.contextMeta, themed.mutedText]}>{helperText}</Text>
          </View>
        </View>

        <View style={[styles.searchPanel, themed.input]}>
          <MaterialIcons name="search" size={21} color={themed.colors.textMuted} />
          <TextInput
            placeholder="Buscar material"
            placeholderTextColor={themed.colors.textSubtle}
            value={search}
            onChangeText={setSearch}
            onSubmitEditing={handleSearch}
            autoCorrect={false}
            style={[styles.searchInput, themed.text]}
          />
          <Pressable onPress={handleSearch} style={[styles.filterButton, themed.primarySurface]}>
            <MaterialIcons name="arrow-forward" size={20} color={themed.colors.primary} />
          </Pressable>
        </View>

        <View style={styles.contentGrid}>
          <View
            style={[styles.materialsPanel, themed.surface]}
            onLayout={(event) => {
              materialsPanelYRef.current = event.nativeEvent.layout.y;
            }}>
            <View style={styles.sectionHeader}>
              <View>
                <Text style={[styles.sectionTitle, themed.text]}>Materiais</Text>
                <Text style={[styles.sectionMeta, themed.mutedText]}>
                  {materialsCounterText}
                </Text>
              </View>
              <View style={[styles.countBadge, themed.primarySurface]}>
                <Text style={[styles.countBadgeText, themed.primaryText]}>{selectedQuantity}</Text>
              </View>
            </View>

            {isLoading ? (
              <View style={[styles.centerState, themed.mutedSurface]}>
                <ActivityIndicator color={themed.colors.primary} />
                <Text style={[styles.centerStateText, themed.mutedText]}>Carregando materiais</Text>
              </View>
            ) : materials.length === 0 ? (
              <View style={[styles.centerState, themed.mutedSurface]}>
                <MaterialIcons name="inventory" size={28} color={themed.colors.textMuted} />
                <Text style={[styles.centerStateText, themed.mutedText]}>Nenhum material encontrado</Text>
              </View>
            ) : (
              materials.map((material) => {
                const itemState = cart[material.id] ?? emptyCartState();

                return (
                  <View style={[styles.materialCard, themed.mutedSurface]} key={material.id}>
                    <View style={[styles.materialThumb, { backgroundColor: themed.colors.surface }]}>
                      <MaterialIcons name={icon} size={24} color={accent} />
                    </View>
                    <View style={styles.materialInfo}>
                      <Text style={[styles.materialName, themed.text]}>{material.descricao}</Text>
                      <Text style={[styles.materialDetail, themed.mutedText]}>
                        {tipo === 'consumo'
                          ? `${material.unidade} | estoque ${material.quantidade_estoque ?? 0}`
                          : 'Material permanente'}
                      </Text>
                      <View style={styles.materialMetaRow}>
                        <Text style={[styles.materialMeta, themed.subtleText]}>{material.unidade}</Text>
                        <Text style={[styles.materialPrice, themed.text]}>{formatMoney(material.preco_medio)}</Text>
                      </View>

                      {tipo === 'permanente' && itemState.quantity > 0 ? (
                        <View style={styles.permanentFields}>
                          <View style={[styles.segmented, { backgroundColor: themed.colors.surfaceAccent }]}>
                            <Pressable
                              onPress={() => setTipoAtendimento(material.id, 'adicao')}
                              style={[
                                styles.segmentButton,
                                itemState.tipoAtendimento === 'adicao' && { backgroundColor: accent },
                              ]}>
                              <Text style={[
                                styles.segmentText,
                                themed.primaryText,
                                itemState.tipoAtendimento === 'adicao' && themed.onPrimaryText,
                              ]}>
                                Adicao
                              </Text>
                            </Pressable>
                            <Pressable
                              onPress={() => setTipoAtendimento(material.id, 'substituicao')}
                              style={[
                                styles.segmentButton,
                                itemState.tipoAtendimento === 'substituicao' && { backgroundColor: accent },
                              ]}>
                              <Text style={[
                                styles.segmentText,
                                themed.primaryText,
                                itemState.tipoAtendimento === 'substituicao' && themed.onPrimaryText,
                              ]}>
                                Substituição
                              </Text>
                            </Pressable>
                          </View>

                          {itemState.tipoAtendimento === 'substituicao' ? (
                            <TextInput
                              placeholder="No. patrimonio substituido"
                              placeholderTextColor={themed.colors.textSubtle}
                              value={itemState.patrimonioSubstituido}
                              onChangeText={(value) => setItemText(material.id, 'patrimonioSubstituido', value)}
                              style={[styles.inlineInput, themed.input]}
                            />
                          ) : null}

                          <TextInput
                            placeholder="Justificativa do item"
                            placeholderTextColor={themed.colors.textSubtle}
                            value={itemState.justificativa}
                            onChangeText={(value) => setItemText(material.id, 'justificativa', value)}
                            multiline
                            style={[styles.inlineTextarea, themed.input]}
                          />
                        </View>
                      ) : null}
                    </View>
                    <View style={styles.quantityBox}>
                      <Pressable
                        accessibilityLabel={`Adicionar ${material.descricao}`}
                        onPress={() => incrementMaterial(material)}
                        style={[
                          styles.quantityButton,
                          { backgroundColor: themed.colors.successSoft },
                        ]}>
                        <MaterialIcons name="add" size={18} color={themed.colors.success} />
                      </Pressable>
                      <Text style={[styles.quantityText, themed.text]}>{itemState.quantity}</Text>
                      <Pressable
                        accessibilityLabel={`Remover ${material.descricao}`}
                        onPress={() => decrementMaterial(material)}
                        style={[
                          styles.quantityButton,
                          { backgroundColor: themed.colors.dangerSoft },
                        ]}>
                        <MaterialIcons name="remove" size={18} color={themed.colors.danger} />
                      </Pressable>
                    </View>
                  </View>
                );
              })
            )}

            {!isLoading && totalMaterials > PER_PAGE ? (
              <View style={[styles.paginationFooter, { borderTopColor: themed.colors.border }]}>
                <Pressable
                  disabled={!hasPreviousPage || isLoading}
                  onPress={() => handlePageChange(currentPage - 1)}
                  style={({ pressed }) => [
                    styles.paginationButton,
                    themed.primarySurface,
                    (!hasPreviousPage || isLoading) && themed.mutedSurface,
                    pressed && hasPreviousPage && styles.pressed,
                  ]}>
                  <MaterialIcons
                    name="chevron-left"
                    size={21}
                    color={hasPreviousPage ? themed.colors.primary : themed.colors.textSubtle}
                  />
                  <Text style={[
                    styles.paginationButtonText,
                    hasPreviousPage ? themed.primaryText : themed.subtleText,
                  ]}>
                    Voltar
                  </Text>
                </Pressable>

                <View style={styles.paginationMeta}>
                  <Text style={[styles.paginationPageText, themed.text]}>Página {currentPage} de {lastPage}</Text>
                  <Text style={[styles.paginationRangeText, themed.mutedText]}>{PER_PAGE} por página</Text>
                </View>

                <Pressable
                  disabled={!hasNextPage || isLoading}
                  onPress={() => handlePageChange(currentPage + 1)}
                  style={({ pressed }) => [
                    styles.paginationButton,
                    themed.primarySurface,
                    (!hasNextPage || isLoading) && themed.mutedSurface,
                    pressed && hasNextPage && styles.pressed,
                  ]}>
                  <Text style={[
                    styles.paginationButtonText,
                    hasNextPage ? themed.primaryText : themed.subtleText,
                  ]}>
                    Avançar
                  </Text>
                  <MaterialIcons
                    name="chevron-right"
                    size={21}
                    color={hasNextPage ? themed.colors.primary : themed.colors.textSubtle}
                  />
                </Pressable>
              </View>
            ) : null}
          </View>

          <View style={[styles.cartPanel, themed.surface]}>
            <View style={styles.sectionHeader}>
              <View>
                <Text style={[styles.sectionTitle, themed.text]}>{summaryTitle}</Text>
                <Text style={[styles.sectionMeta, themed.mutedText]}>{selectedQuantity} item(ns) selecionado(s)</Text>
              </View>
              <View style={[styles.cartIcon, { backgroundColor: `${accent}22` }]}>
                <MaterialIcons name="shopping-cart" size={22} color={accent} />
              </View>
            </View>

            {selectedItems.length > 0 ? (
              selectedItems.map(({ material, state }) => (
                <View style={[styles.cartRow, themed.mutedSurface]} key={material.id}>
                  <View style={[styles.cartQuantity, themed.primarySurface]}>
                    <Text style={[styles.cartQuantityText, themed.primaryText]}>{state.quantity}</Text>
                  </View>
                  <View style={styles.cartInfo}>
                    <Text style={[styles.cartName, themed.text]}>{material.descricao}</Text>
                    <Text style={[styles.cartDetail, themed.mutedText]}>
                      {tipo === 'permanente'
                        ? state.tipoAtendimento === 'substituicao' ? 'Substituição' : 'Adição'
                        : material.unidade}
                    </Text>
                  </View>
                </View>
              ))
            ) : (
              <View style={[styles.emptyCart, themed.mutedSurface]}>
                <MaterialIcons name="shopping-cart" size={26} color={themed.colors.textMuted} />
                <Text style={[styles.emptyCartText, themed.mutedText]}>Nenhum material adicionado</Text>
              </View>
            )}

            <View style={[styles.formPreview, { borderTopColor: themed.colors.border }]}>
              <Text style={[styles.formLabel, themed.mutedText]}>Complemento do setor</Text>
              <Pressable
                onPress={() => setIsComplementoModalVisible(true)}
                style={[styles.fakeInput, themed.input]}>
                <Text
                  style={[
                    selectedComplemento ? styles.fakeInputValue : styles.fakeInputText,
                    selectedComplemento ? themed.text : themed.subtleText,
                  ]}>
                  {selectedComplemento?.descricao ?? 'Selecionar destino'}
                </Text>
                <MaterialIcons name="keyboard-arrow-down" size={22} color={themed.colors.textMuted} />
              </Pressable>

              <Text style={[styles.formLabel, themed.mutedText]}>
                {tipo === 'consumo' ? 'Justificativa' : 'Observação geral'}
              </Text>
              <TextInput
                placeholder={tipo === 'consumo'
                  ? 'Descrever necessidade do pedido'
                  : 'Opcional para o cabecalho do pedido'}
                placeholderTextColor={themed.colors.textSubtle}
                value={justificativaGeral}
                onChangeText={setJustificativaGeral}
                multiline
                style={[styles.fakeTextarea, themed.input]}
              />
            </View>

            <View style={styles.cartActions}>
              <Pressable
                disabled={isSubmitting}
                onPress={clearCart}
                style={[styles.secondaryButton, themed.dangerSurface]}>
                <MaterialIcons name="delete-outline" size={20} color={themed.colors.danger} />
                <Text style={[styles.secondaryButtonText, { color: themed.colors.danger }]}>Limpar</Text>
              </Pressable>
              <Pressable
                disabled={isSubmitting}
                onPress={submitPedido}
                style={[styles.primaryButton, { backgroundColor: accent }, isSubmitting && styles.disabledButton]}>
                {isSubmitting ? (
                  <ActivityIndicator color={themed.colors.primaryText} />
                ) : (
                  <MaterialIcons name="send" size={20} color={themed.colors.primaryText} />
                )}
                <Text style={[styles.primaryButtonText, themed.onPrimaryText]}>
                  {isSubmitting ? 'Enviando' : 'Enviar'}
                </Text>
              </Pressable>
            </View>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F4F7FA',
  },
  scroll: {
    flex: 1,
  },
  content: {
    gap: 14,
    padding: 18,
    paddingBottom: 28,
  },
  notification: {
    minHeight: 48,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 9,
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  notificationSuccess: {
    backgroundColor: '#2F855A',
  },
  notificationError: {
    backgroundColor: '#C53030',
  },
  notificationInfo: {
    backgroundColor: '#1E4E79',
  },
  notificationText: {
    flex: 1,
    color: '#FFFFFF',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '800',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
  },
  headerTextGroup: {
    flex: 1,
    gap: 3,
  },
  eyebrow: {
    color: '#627D98',
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  title: {
    color: '#102A43',
    fontSize: 26,
    fontWeight: '800',
  },
  subtitle: {
    color: '#52616B',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '700',
  },
  headerIcon: {
    width: 44,
    height: 44,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
  },
  tabs: {
    minHeight: 48,
    flexDirection: 'row',
    gap: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    padding: 6,
  },
  tab: {
    minHeight: 36,
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    borderRadius: 8,
  },
  tabText: {
    color: '#1E4E79',
    fontSize: 13,
    fontWeight: '800',
  },
  tabTextActive: {
    color: '#FFFFFF',
  },
  contextPanel: {
    flexDirection: 'row',
    gap: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    padding: 14,
  },
  contextIcon: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  contextText: {
    flex: 1,
    gap: 2,
  },
  contextTitle: {
    color: '#102A43',
    fontSize: 15,
    fontWeight: '800',
  },
  contextMeta: {
    color: '#52616B',
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '700',
  },
  searchPanel: {
    minHeight: 50,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 12,
  },
  searchInput: {
    flex: 1,
    color: '#102A43',
    fontSize: 14,
    fontWeight: '800',
  },
  filterButton: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  contentGrid: {
    gap: 14,
  },
  materialsPanel: {
    gap: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    padding: 14,
  },
  sectionHeader: {
    minHeight: 42,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  sectionTitle: {
    color: '#102A43',
    fontSize: 17,
    fontWeight: '800',
  },
  sectionMeta: {
    color: '#627D98',
    fontSize: 12,
    fontWeight: '700',
  },
  countBadge: {
    minWidth: 34,
    height: 34,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  countBadgeText: {
    color: '#1E4E79',
    fontSize: 14,
    fontWeight: '800',
  },
  materialCard: {
    minHeight: 112,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#E5EAF0',
    backgroundColor: '#F8FAFC',
    padding: 10,
  },
  materialThumb: {
    width: 54,
    height: 54,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#FFFFFF',
  },
  materialInfo: {
    flex: 1,
    gap: 4,
  },
  materialName: {
    color: '#102A43',
    fontSize: 15,
    fontWeight: '800',
  },
  materialDetail: {
    color: '#52616B',
    fontSize: 12,
    fontWeight: '700',
  },
  materialMetaRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  materialMeta: {
    color: '#627D98',
    fontSize: 12,
    fontWeight: '800',
  },
  materialPrice: {
    color: '#102A43',
    fontSize: 12,
    fontWeight: '800',
  },
  quantityBox: {
    width: 38,
    alignItems: 'center',
    gap: 5,
  },
  quantityButton: {
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#FFFFFF',
  },
  quantityButtonAdd: {
    backgroundColor: '#F0FFF4',
  },
  quantityButtonRemove: {
    backgroundColor: '#FFF5F5',
  },
  quantityText: {
    color: '#102A43',
    fontSize: 16,
    fontWeight: '800',
  },
  permanentFields: {
    gap: 8,
    paddingTop: 4,
  },
  segmented: {
    minHeight: 38,
    flexDirection: 'row',
    gap: 6,
    borderRadius: 8,
    backgroundColor: '#EEF5FA',
    padding: 4,
  },
  segmentButton: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
  },
  segmentText: {
    color: '#1E4E79',
    fontSize: 12,
    fontWeight: '800',
  },
  segmentTextActive: {
    color: '#FFFFFF',
  },
  inlineInput: {
    minHeight: 42,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    color: '#102A43',
    fontSize: 13,
    fontWeight: '700',
    paddingHorizontal: 10,
  },
  inlineTextarea: {
    minHeight: 68,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    color: '#102A43',
    fontSize: 13,
    fontWeight: '700',
    padding: 10,
    textAlignVertical: 'top',
  },
  centerState: {
    minHeight: 120,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 8,
    backgroundColor: '#F8FAFC',
  },
  centerStateText: {
    color: '#52616B',
    fontSize: 13,
    fontWeight: '800',
  },
  paginationFooter: {
    minHeight: 58,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderTopWidth: 1,
    borderTopColor: '#E5EAF0',
    paddingTop: 10,
  },
  paginationButton: {
    minHeight: 42,
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  paginationButtonDisabled: {
    backgroundColor: '#F8FAFC',
  },
  paginationButtonText: {
    color: '#1E4E79',
    fontSize: 13,
    fontWeight: '800',
  },
  paginationButtonTextDisabled: {
    color: '#9FB3C8',
  },
  paginationMeta: {
    minWidth: 88,
    alignItems: 'center',
    gap: 2,
  },
  paginationPageText: {
    color: '#102A43',
    fontSize: 12,
    fontWeight: '800',
  },
  paginationRangeText: {
    color: '#627D98',
    fontSize: 11,
    fontWeight: '700',
  },
  cartPanel: {
    gap: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    padding: 14,
  },
  cartIcon: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
  },
  cartRow: {
    minHeight: 54,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 8,
    backgroundColor: '#F8FAFC',
    padding: 10,
  },
  cartQuantity: {
    width: 34,
    height: 34,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  cartQuantityText: {
    color: '#1E4E79',
    fontSize: 14,
    fontWeight: '800',
  },
  cartInfo: {
    flex: 1,
    gap: 2,
  },
  cartName: {
    color: '#102A43',
    fontSize: 14,
    fontWeight: '800',
  },
  cartDetail: {
    color: '#627D98',
    fontSize: 12,
    fontWeight: '700',
  },
  emptyCart: {
    minHeight: 86,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 7,
    borderRadius: 8,
    backgroundColor: '#F8FAFC',
  },
  emptyCartText: {
    color: '#52616B',
    fontSize: 13,
    fontWeight: '800',
  },
  formPreview: {
    gap: 8,
    borderTopWidth: 1,
    borderTopColor: '#E5EAF0',
    paddingTop: 12,
  },
  formLabel: {
    color: '#334E68',
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  fakeInput: {
    minHeight: 44,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 12,
  },
  fakeTextarea: {
    minHeight: 86,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    color: '#102A43',
    fontSize: 13,
    fontWeight: '700',
    padding: 12,
    textAlignVertical: 'top',
  },
  fakeInputText: {
    flex: 1,
    color: '#829AB1',
    fontSize: 13,
    fontWeight: '700',
  },
  fakeInputValue: {
    flex: 1,
    color: '#102A43',
    fontSize: 13,
    fontWeight: '800',
  },
  cartActions: {
    flexDirection: 'row',
    gap: 10,
  },
  secondaryButton: {
    minHeight: 48,
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#F1C4C4',
    backgroundColor: '#FFF5F5',
  },
  secondaryButtonText: {
    color: '#C53030',
    fontSize: 14,
    fontWeight: '800',
  },
  primaryButton: {
    minHeight: 48,
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    borderRadius: 8,
  },
  primaryButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '800',
  },
  disabledButton: {
    opacity: 0.72,
  },
  modalOverlay: {
    flex: 1,
    justifyContent: 'center',
    backgroundColor: '#102A4399',
    padding: 18,
  },
  modalPanel: {
    maxHeight: '78%',
    overflow: 'hidden',
    borderRadius: 8,
    backgroundColor: '#FFFFFF',
  },
  modalHeader: {
    minHeight: 62,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#D9E2EC',
    padding: 14,
  },
  modalTitle: {
    color: '#102A43',
    fontSize: 18,
    fontWeight: '800',
  },
  modalCloseButton: {
    width: 38,
    height: 38,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  modalContent: {
    gap: 8,
    padding: 14,
  },
  complementoOption: {
    minHeight: 48,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
    borderRadius: 8,
    backgroundColor: '#F8FAFC',
    paddingHorizontal: 12,
  },
  complementoOptionActive: {
    backgroundColor: '#1E4E79',
  },
  complementoOptionText: {
    flex: 1,
    color: '#102A43',
    fontSize: 13,
    fontWeight: '800',
  },
  complementoOptionTextActive: {
    color: '#FFFFFF',
  },
  pressed: {
    opacity: 0.72,
  },
});
