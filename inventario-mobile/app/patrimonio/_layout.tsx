import { Stack } from 'expo-router';
import { useCallback, useMemo, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { Gesture, GestureDetector } from 'react-native-gesture-handler';
import { runOnJS } from 'react-native-reanimated';
import { AppSidebar } from '@/components/app-sidebar';
import { getPatrimonioStackAnimation } from '@/src/navigation/patrimonioNavigation';

const EDGE_SWIPE_WIDTH = 28;
const MIN_OPEN_DISTANCE = 64;
const MAX_VERTICAL_DRIFT = 48;

export default function PatrimonioLayout() {
  const [isSidebarVisible, setIsSidebarVisible] = useState(false);

  const openSidebar = useCallback(() => {
    setIsSidebarVisible(true);
  }, []);

  const edgeSwipeGesture = useMemo(() => {
    return Gesture.Pan()
      .hitSlop({ left: 0, width: EDGE_SWIPE_WIDTH })
      .minDistance(18)
      .onEnd((event) => {
        if (
          event.translationX >= MIN_OPEN_DISTANCE
          && Math.abs(event.translationY) <= MAX_VERTICAL_DRIFT
        ) {
          runOnJS(openSidebar)();
        }
      });
  }, [openSidebar]);

  return (
    <>
      <GestureDetector gesture={edgeSwipeGesture}>
        <View style={styles.container}>
          <Stack
            screenOptions={() => ({
              headerShown: false,
              animation: getPatrimonioStackAnimation(),
              animationDuration: 240,
              gestureEnabled: false,
            })}>
            <Stack.Screen name="index" options={{ headerShown: false }} />
            <Stack.Screen name="principal" options={{ headerShown: false }} />
            <Stack.Screen name="bens" options={{ headerShown: false }} />
            <Stack.Screen name="conferencia" options={{ headerShown: false }} />
          </Stack>
        </View>
      </GestureDetector>
      <AppSidebar
        visible={isSidebarVisible}
        onClose={() => setIsSidebarVisible(false)}
      />
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
});
