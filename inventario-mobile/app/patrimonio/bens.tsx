import MaterialIcons from '@expo/vector-icons/MaterialIcons';
import { router } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { AppMenuButton } from '@/components/app-menu-button';
import { BottomBar } from '@/components/bottom-bar';
import { authApi, type MobileUser } from '@/src/api/auth';
import { bensApi, type BemPatrimonial } from '@/src/api/bens';
import { ApiError, NetworkError } from '@/src/api/errors';
import { useThemeStyles } from '@/src/theme/useThemeStyles';

const BENS_PER_PAGE = 30;

function displayValue(value: unknown, fallback = '-'): string {
  if (value === null || value === undefined || value === '') {
    return fallback;
  }

  return String(value);
}

function getBemCodigo(bem: BemPatrimonial): string {
  return displayValue(
    bem.codigo
      ?? bem.patrimonio
      ?? bem.codigo_patrimonial
      ?? bem.tombamento
      ?? bem.tombo_smarapd
      ?? bem.num_tombo_smarapd
      ?? bem.id,
    'Sem código',
  );
}

function getBemDescricao(bem: BemPatrimonial): string {
  return displayValue(bem.descricao_resumida ?? bem.descricao ?? bem.denominacao, 'Bem patrimonial');
}

function getBemSituacao(bem: BemPatrimonial): string {
  return displayValue(bem.situacao ?? bem.estado, 'No setor');
}

function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError || error instanceof NetworkError) {
    return error.message;
  }

  return 'Não foi possível carregar os bens do setor.';
}

export default function BensScreen() {
  const themed = useThemeStyles();
  const [user, setUser] = useState<MobileUser | null>(null);
  const [bens, setBens] = useState<BemPatrimonial[]>([]);
  const [total, setTotal] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [search, setSearch] = useState('');
  const [submittedSearch, setSubmittedSearch] = useState('');
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const loadBens = useCallback(async ({
    pageToLoad = 1,
    append = false,
    refreshing = false,
    searchTerm = '',
  }: {
    pageToLoad?: number;
    append?: boolean;
    refreshing?: boolean;
    searchTerm?: string;
  } = {}) => {
    if (append) {
      setIsLoadingMore(true);
    } else if (refreshing) {
      setIsRefreshing(true);
    } else {
      setIsLoading(true);
    }

    if (!append) {
      setErrorMessage(null);
    }

    try {
      const session = await authApi.getStoredSession();

      if (!session) {
        router.replace('/');
        return;
      }

      setUser(session.user);

      const result = await bensApi.listByUserSector({
        page: pageToLoad,
        perPage: BENS_PER_PAGE,
        search: searchTerm,
      });

      setBens((currentBens) => append ? [...currentBens, ...result.bens] : result.bens);
      setTotal(result.total);
      setCurrentPage(result.meta.current_page);
      setHasMore(result.meta.has_more);
    } catch (error) {
      if (append) {
        setHasMore(false);
      } else {
        setErrorMessage(getErrorMessage(error));
      }
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
      setIsLoadingMore(false);
    }
  }, []);

  useEffect(() => {
    loadBens();
  }, [loadBens]);

  const handleRefresh = useCallback(() => {
    loadBens({
      pageToLoad: 1,
      refreshing: true,
      searchTerm: submittedSearch,
    });
  }, [loadBens, submittedSearch]);

  const handleLoadMore = useCallback(() => {
    if (isLoading || isRefreshing || isLoadingMore || !hasMore) {
      return;
    }

    loadBens({
      pageToLoad: currentPage + 1,
      append: true,
      searchTerm: submittedSearch,
    });
  }, [
    currentPage,
    hasMore,
    isLoading,
    isLoadingMore,
    isRefreshing,
    loadBens,
    submittedSearch,
  ]);

  const handleSearch = useCallback(() => {
    const nextSearch = search.trim();

    setSubmittedSearch(nextSearch);
    loadBens({
      pageToLoad: 1,
      searchTerm: nextSearch,
    });
  }, [loadBens, search]);

  const handleClearSearch = useCallback(() => {
    setSearch('');
    setSubmittedSearch('');
    loadBens({
      pageToLoad: 1,
      searchTerm: '',
    });
  }, [loadBens]);

  function renderBem({ item }: { item: BemPatrimonial }) {
    return (
      <View style={[styles.bemRow, themed.surface]}>
        <View style={[styles.bemIcon, themed.primarySurface]}>
          <MaterialIcons name="inventory-2" size={21} color={themed.colors.primary} />
        </View>
        <View style={styles.bemInfo}>
          <Text style={[styles.bemCodigo, themed.text]}>{getBemCodigo(item)}</Text>
          <Text style={[styles.bemDescricao, themed.mutedText]} numberOfLines={2}>
            {getBemDescricao(item)}
          </Text>
          <View style={styles.bemMetaRow}>
            <Text style={[styles.bemMeta, themed.subtleText]}>Marca: {displayValue(item.marca)}</Text>
            <Text style={[styles.bemMeta, themed.subtleText]}>Modelo: {displayValue(item.modelo)}</Text>
            <Text style={[styles.bemMeta, themed.subtleText]}>Série: {displayValue(item.numero_serie ?? item.serie)}</Text>
          </View>
        </View>
        <View style={[styles.statusBadge, { backgroundColor: themed.colors.successSoft }]}>
          <Text style={[styles.statusBadgeText, { color: themed.colors.success }]}>{getBemSituacao(item)}</Text>
        </View>
      </View>
    );
  }

  return (
    <SafeAreaView style={[styles.safeArea, themed.screen]}>
      <View style={styles.header}>
        <AppMenuButton />
        <View style={styles.headerTextGroup}>
          <Text style={[styles.eyebrow, themed.mutedText]}>Bens do setor</Text>
          <Text style={[styles.title, themed.text]}>Patrimônio localizado</Text>
        </View>
      </View>

      <View style={[styles.contextPanel, themed.surface]}>
        <View style={[styles.contextIcon, themed.primarySurface]}>
          <MaterialIcons name="apartment" size={23} color={themed.colors.primary} />
        </View>
        <View style={styles.contextText}>
          <Text style={[styles.contextLabel, themed.text]}>{user?.name ?? user?.login ?? 'Usuário mobile'}</Text>
          <Text style={[styles.contextMeta, themed.mutedText]}>
            Unidade {user?.unidade_judiciaria ?? '-'} | Setor {user?.setor ?? '-'}
          </Text>
        </View>
        <View style={[styles.totalBadge, themed.primarySurface]}>
          <Text style={[styles.totalValue, themed.primaryText]}>{total}</Text>
          <Text style={[styles.totalLabel, themed.mutedText]}>bens</Text>
        </View>
      </View>

      <View style={styles.searchPanel}>
        <View style={[styles.searchInputWrapper, themed.input]}>
          <MaterialIcons name="search" size={21} color={themed.colors.textMuted} />
          <TextInput
            placeholder="Buscar por patrimônio, descrição, marca ou série"
            placeholderTextColor={themed.colors.textSubtle}
            autoCorrect={false}
            returnKeyType="search"
            value={search}
            onChangeText={setSearch}
            onSubmitEditing={handleSearch}
            style={[styles.searchInput, themed.text]}
          />
          {search ? (
            <Pressable onPress={handleClearSearch} style={[styles.clearSearchButton, themed.primarySurface]}>
              <MaterialIcons name="close" size={18} color={themed.colors.primary} />
            </Pressable>
          ) : null}
        </View>
        <Pressable
          disabled={isLoading}
          onPress={handleSearch}
          style={({ pressed }) => [
            styles.searchButton,
            { backgroundColor: themed.colors.primary },
            (pressed || isLoading) && styles.searchButtonPressed,
          ]}>
          <Text style={[styles.searchButtonText, themed.onPrimaryText]}>Buscar</Text>
        </Pressable>
      </View>

      {isLoading ? (
        <View style={styles.centerState}>
          <ActivityIndicator color={themed.colors.primary} />
          <Text style={[styles.centerStateText, themed.mutedText]}>Carregando bens do setor</Text>
        </View>
      ) : errorMessage ? (
        <View style={styles.centerState}>
          <MaterialIcons name="error-outline" size={30} color={themed.colors.danger} />
          <Text style={[styles.errorTitle, { color: themed.colors.danger }]}>Falha ao carregar</Text>
          <Text style={[styles.centerStateText, themed.mutedText]}>{errorMessage}</Text>
          <Pressable
            onPress={() => loadBens({ pageToLoad: 1 })}
            style={[styles.retryButton, { backgroundColor: themed.colors.primary }]}>
            <MaterialIcons name="refresh" size={20} color={themed.colors.primaryText} />
            <Text style={[styles.retryButtonText, themed.onPrimaryText]}>Tentar novamente</Text>
          </Pressable>
        </View>
      ) : (
        <FlatList
          data={bens}
          keyExtractor={(item, index) => `${getBemCodigo(item)}-${item.id}-${index}`}
          renderItem={renderBem}
          contentContainerStyle={bens.length > 0 ? styles.listContent : styles.emptyContent}
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.35}
          refreshControl={
            <RefreshControl
              refreshing={isRefreshing}
              onRefresh={handleRefresh}
              tintColor={themed.colors.primary}
              colors={[themed.colors.primary]}
            />
          }
          ListFooterComponent={
            bens.length > 0 ? (
              <View style={styles.listFooter}>
                {isLoadingMore ? (
                  <>
                    <ActivityIndicator color={themed.colors.primary} />
                    <Text style={[styles.listFooterText, themed.mutedText]}>Carregando mais bens</Text>
                  </>
                ) : hasMore ? (
                  <Text style={[styles.listFooterText, themed.mutedText]}>Role para carregar mais</Text>
                ) : (
                  <Text style={[styles.listFooterText, themed.mutedText]}>Todos os bens foram carregados</Text>
                )}
              </View>
            ) : null
          }
          ListEmptyComponent={
            <View style={[styles.emptyPanel, themed.surface]}>
              <MaterialIcons name="inventory" size={32} color={themed.colors.textMuted} />
              <Text style={[styles.emptyTitle, themed.text]}>Nenhum bem encontrado</Text>
              <Text style={[styles.emptyText, themed.mutedText]}>
                A API não retornou bens para o setor vinculado ao seu usuário.
              </Text>
            </View>
          }
        />
      )}
      <BottomBar />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F4F7FA',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 20,
    paddingTop: 10,
    paddingBottom: 14,
  },
  iconButton: {
    width: 42,
    height: 42,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#B6D4EA',
    backgroundColor: '#EAF4FB',
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
    fontSize: 24,
    fontWeight: '800',
  },
  contextPanel: {
    minHeight: 72,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginHorizontal: 20,
    marginBottom: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    padding: 14,
  },
  searchPanel: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginHorizontal: 20,
    marginBottom: 12,
  },
  searchInputWrapper: {
    minHeight: 48,
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 9,
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
    fontWeight: '700',
  },
  clearSearchButton: {
    width: 30,
    height: 30,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  searchButton: {
    minHeight: 48,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#1E4E79',
    paddingHorizontal: 14,
  },
  searchButtonPressed: {
    opacity: 0.72,
  },
  searchButtonText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '800',
  },
  contextIcon: {
    width: 42,
    height: 42,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  contextText: {
    flex: 1,
    gap: 3,
  },
  contextLabel: {
    color: '#102A43',
    fontSize: 15,
    fontWeight: '800',
  },
  contextMeta: {
    color: '#52616B',
    fontSize: 13,
    fontWeight: '700',
  },
  totalBadge: {
    minWidth: 58,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
    paddingHorizontal: 10,
    paddingVertical: 8,
  },
  totalValue: {
    color: '#1E4E79',
    fontSize: 18,
    fontWeight: '800',
  },
  totalLabel: {
    color: '#52616B',
    fontSize: 11,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  listContent: {
    gap: 10,
    paddingHorizontal: 20,
    paddingBottom: 24,
  },
  listFooter: {
    minHeight: 54,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingTop: 6,
  },
  listFooterText: {
    color: '#627D98',
    fontSize: 13,
    fontWeight: '800',
    textAlign: 'center',
  },
  bemRow: {
    minHeight: 92,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    padding: 12,
  },
  bemIcon: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  bemInfo: {
    flex: 1,
    gap: 5,
  },
  bemCodigo: {
    color: '#102A43',
    fontSize: 15,
    fontWeight: '800',
  },
  bemDescricao: {
    color: '#334E68',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '700',
  },
  bemMetaRow: {
    gap: 2,
  },
  bemMeta: {
    color: '#627D98',
    fontSize: 12,
    fontWeight: '700',
  },
  statusBadge: {
    maxWidth: 92,
    borderRadius: 8,
    backgroundColor: '#E6F4EA',
    paddingHorizontal: 8,
    paddingVertical: 6,
  },
  statusBadgeText: {
    color: '#2F855A',
    fontSize: 11,
    fontWeight: '800',
    textAlign: 'center',
  },
  centerState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    padding: 24,
  },
  centerStateText: {
    color: '#52616B',
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '700',
    textAlign: 'center',
  },
  errorTitle: {
    color: '#9B2C2C',
    fontSize: 18,
    fontWeight: '800',
  },
  retryButton: {
    minHeight: 46,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 8,
    backgroundColor: '#1E4E79',
    paddingHorizontal: 16,
  },
  retryButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '800',
  },
  emptyContent: {
    flexGrow: 1,
    justifyContent: 'center',
    paddingHorizontal: 20,
    paddingBottom: 24,
  },
  emptyPanel: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    padding: 22,
  },
  emptyTitle: {
    color: '#102A43',
    fontSize: 18,
    fontWeight: '800',
  },
  emptyText: {
    color: '#52616B',
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '700',
    textAlign: 'center',
  },
});
